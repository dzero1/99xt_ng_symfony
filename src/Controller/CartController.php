<?php

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\CartRepository;
use App\Repository\CouponRepository;
use App\Repository\DiscountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class CartController extends BaseController
{
    public function __construct(Security $security, 
        BookRepository $bookRepository,
        CartRepository $cartRepository,
        DiscountRepository $discountRepository,
        CouponRepository $couponRepository
    ) {
        $this->security = $security;
        $this->bookRepository = $bookRepository;
        $this->cartRepository = $cartRepository;
        $this->discountRepository = $discountRepository;
        $this->couponRepository = $couponRepository;
    }

    /**
     * @Route("/api/cart", name="cart")
     */
    public function index(): Response
    {
        return $this->render('cart/index.html.twig', [
            'controller_name' => 'CartController',
        ]);
    }

    /**
     * @Route("/api/cart/add/{id}", name="add_to_cart", methods={"POST"}, format="json")
    */
    public function addToCart($id): Response
    {
        if ($id){
            // Get book
            $book = $this->bookRepository->findOneBy(['id' => $id]);
            if ($book){
                $cart = $this->cartRepository->addToCart($book);
                
                return $this->response([
                    "cartId" => $cart->getId(),
                    "total" => $this->calcCartPriceTotal()
                ]);
            } else {
                return $this->response("Book not found.", 'error');
            }
        } else {
            return $this->response("Book id not sent.", 'error');
        }
    }

    /**
     * @Route("/api/cart/remove/{id}", name="remove_from_cart", methods={"DELETE"}, format="json")
     */
    public function removeFromCart($id): Response
    {
        if ($id){
            $cartItem = $this->cartRepository->findOneBy(['book' => $id]);
            if ($cartItem){
                $removeingId = $cartItem->getId();
                $this->cartRepository->removeFromCart($cartItem);
                return $this->response([
                    "removedCartId" => $removeingId,
                    "total" => $this->calcCartPriceTotal()
                ]);
            } else {
                return $this->response("This book is not in your cart.", 'error');
            }
        } else {
            return $this->response("What book do you want to remove from your cart?", 'error');
        }
    }
    
    /**
     * @Route("/api/cart/clear", name="clear_cart", methods={"DELETE"}, format="json")
     */
    public function clearCart(): Response
    {
        $this->cartRepository->clearCart();
        return $this->response([]);
    }

    /**
     * @Route("/api/cart/total", name="cart_price_total", methods={"GET"}, format="json")
     */
    public function getCartPriceTotal(): Response
    {
        return $this->response($this->calcCartPriceTotal());
    }

    /**
     * @Route("/api/cart/invoice", name="cart_invoice", methods={"GET"}, format="json")
     */
    public function getCartInvoice(Request $request): Response
    {
        $invoice = [];
        $user = $this->security->getUser();
        $data = json_decode($request->getContent(), true);

        $couponCode = isset($data['couponCode']) ? $data['couponCode'] : false;

        $cartItems = $this->cartRepository->findBy(['user' => $user]);
        if ($cartItems){
            $cartBooks = [];
            foreach ($cartItems as $item) {
                $catData = [];
                $book = $item->getBook();

                $catData['id'] = $book->getId();
                $catData['cover'] = $book->getCover();
                $catData['name'] = $book->getTitle();
                $catData['price'] = $book->getPrice();
                
                // Set category data
                $cartBooks[] = $catData;
            }
            $invoice['books'] = $cartBooks;
        }
        $invoice['financial'] = $this->calcCartPriceTotal($couponCode, true);

        return $this->response($invoice);
    }

    // Calculate the final total and discounts
    private function calcCartPriceTotal($couponCode = false, $fullDetails = false){
        $total = 0;
        $grandTotal = 0;
        $categoryWiseTotal = [];
        $categoryItemCount = [];

        $detailBookWiseDiscounts = [];
        $totalDiscounts = 0;
        $categoryWiseDiscountTotal = [];
        $allCategoryWiseDiscountTotal = 0;
        $siteWideDiscountTotal = 0;
        $couponDiscountTotal = 0;
        
        $user = $this->security->getUser();
        
        // Map category wise book amounts
        $cartItems = $this->cartRepository->findBy(['user' => $user]);
        if ($cartItems){
            foreach ($cartItems as $item) {
                $book = $item->getBook();
                $bookPrice = $book->getPrice();
                
                $grandTotal += $bookPrice;

                $bookDiscountPercentage = 0;
                $bookDiscountAmount = 0;
                $bookDiscounts = $this->discountRepository->findByBook($book);
                foreach($bookDiscounts as $discount){
                    if ($discount->getDiscountType() == 'PERCENTAGE'){
                        $bookDiscountPercentage += $discount->getAmount();
                    } else {
                        $bookDiscountAmount += $discount->getAmount();
                    }
                }

                if ($bookDiscountPercentage > 50) $bookDiscountPercentage = 50; // Discount percentage cannot go beyond 50%
                
                // Apply book discounts
                $discountAmount = ($bookPrice * ($bookDiscountPercentage/100)) + $bookDiscountAmount;
                $bookPrice -= $discountAmount;

                $detailBookWiseDiscounts[$book->getId()] = $discountAmount;

                // Book price cannot be minus
                if ($bookPrice < 0) $bookPrice = 0;

                $categories = $book->getBookCategories();
                foreach($categories as $category){
                    $categoryId = $category->getCategory()->getId();
                    if (!isset($categoryItemCount[$categoryId])) $categoryItemCount[$categoryId] = 0;
                    $categoryItemCount[$categoryId]++;

                    if (!isset($categoryWiseTotal[$categoryId])) $categoryWiseTotal[$categoryId] = 0;
                    $categoryWiseTotal[$categoryId] += $bookPrice;
                }
            }
        }

        // Applying category wise discounts =======================================

            $categoryWiseDiscountsPercentage = [];
            $categoryWiseDiscountsAmount = [];
            $categoryWiseDiscounts = $this->discountRepository->findCategoryWiseDiscounts();
            foreach ($categoryWiseDiscounts as $discount){
                switch ($discount->getRelateType()) {
                    case 'CATEGORY':
                        if ($discount->getQty() != null){
                            $categoryId = $discount->getRelateId();

                            // Set discount inital to 0
                            if (!isset($categoryWiseDiscountsPercentage[$categoryId])) $categoryWiseDiscountsPercentage[$categoryId] = 0;
                            if (!isset($categoryWiseDiscountsAmount[$categoryId])) $categoryWiseDiscountsAmount[$categoryId] = 0;

                            if (isset($categoryItemCount[$categoryId])){
                                $categoryCount = $categoryItemCount[$categoryId];
                                if ($categoryCount && $categoryCount >= $discount->getQty()){
                                    if ($discount->getDiscountType() == 'PERCENTAGE'){
                                        $categoryWiseDiscountsPercentage[$categoryId] += $discount->getAmount();
                                    } else {
                                        $categoryWiseDiscountsAmount[$categoryId] += $discount->getAmount();
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        break;
                }
            }

            // Apply category wise discount percentage
            foreach ($categoryWiseDiscountsPercentage as $categoryId => $discountPercentage) {
                if ($discountPercentage > 50) $discountPercentage = 50; // Discount percentage cannot go beyond 50%

                if (isset($categoryWiseTotal[$categoryId])){
                    $discountAmounts = $categoryWiseTotal[$categoryId] * ($discountPercentage/100);
                    $totalDiscounts += $discountAmounts;
                    
                    if (!isset($categoryWiseDiscountTotal[$categoryId])) $categoryWiseDiscountTotal[$categoryId] = 0;
                    $categoryWiseDiscountTotal[$categoryId] += number_format((float) $discountAmounts, 2, '.', '');

                    $categoryWiseTotal[$categoryId] -= $discountAmounts;
                }
            }

            // Apply category wise discount amount
            foreach ($categoryWiseDiscountsAmount as $categoryId => $discountAmounts) {
                $totalDiscounts += $discountAmounts;

                if (!isset($categoryWiseDiscountTotal[$categoryId])) $categoryWiseDiscountTotal[$categoryId] = 0;
                $categoryWiseDiscountTotal[$categoryId] += number_format((float) $discountAmounts, 2, '.', '');
                
                if (isset($categoryWiseTotal[$categoryId])) $categoryWiseTotal[$categoryId] -= $discountAmounts;
            }

        // End of applying category wise discounts ================================

        // ************************************************************************

        // Applying all category wise discounts ===================================

            $allCategoryWiseDiscountsPercentage = [];
            $allCategoryWiseDiscountsAmount = [];
            $allCategoryWiseDiscounts = $this->discountRepository->findAllCategoryDiscounts();
            foreach ($allCategoryWiseDiscounts as $discount){
                switch ($discount->getRelateType()) {
                    case 'CATEGORY':
                        if ($discount->getQty() != null){
                            foreach ($categoryItemCount as $categoryId => $categoryCount) {

                                // Set discount inital to 0
                                if (!isset($allCategoryWiseDiscountsPercentage[$categoryId])) $allCategoryWiseDiscountsPercentage[$categoryId] = 0;
                                if (!isset($allCategoryWiseDiscountsAmount[$categoryId])) $allCategoryWiseDiscountsAmount[$categoryId] = 0;

                                if ($categoryCount >= $discount->getQty()){
                                    if ($discount->getDiscountType() == 'PERCENTAGE'){
                                        $allCategoryWiseDiscountsPercentage[$categoryId] += $discount->getAmount();
                                    } else {
                                        $allCategoryWiseDiscountsAmount[$categoryId] += $discount->getAmount();
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        break;
                }
            }

            // Apply category wise discount percentage
            foreach ($allCategoryWiseDiscountsPercentage as $categoryId => $discountPercentage) {
                if ($discountPercentage > 50) $discountPercentage = 50; // Discount percentage cannot go beyond 50%

                $discountAmounts = $categoryWiseTotal[$categoryId] * ($discountPercentage/100);
                $allCategoryWiseDiscountTotal += $discountAmounts;
                $totalDiscounts += $discountAmounts;
                $categoryWiseTotal[$categoryId] -= $discountAmounts;
            }

            // Apply category wise discount amount
            foreach ($allCategoryWiseDiscountsAmount as $categoryId => $discountAmounts) {
                $allCategoryWiseDiscountTotal += $discountAmounts;
                $totalDiscounts += $discountAmounts;
                $categoryWiseTotal[$categoryId] -= $discountAmounts;
            }

        // End of applying all category wise discounts ============================

        
        // ************************************************************************

        // Before apply site wide discount now we calculate the sum total amount

        $total = $grandTotal - $totalDiscounts;

        // Applying site wide discounts ===========================================

            $siteWideDiscountsPercentageTotal = 0;
            $siteWideDiscountsAmountTotal = 0;
            $siteWideDiscounts = $this->discountRepository->findSiteWideDiscounts();
            foreach ($siteWideDiscounts as $discount){
                switch ($discount->getRelateType()) {
                    case 'CATEGORY':
                        if ($discount->getQty() != null){
                            foreach ($categoryItemCount as $categoryCount) {
                                if ($categoryCount >= $discount->getQty()){
                                    if ($discount->getDiscountType() == 'PERCENTAGE'){
                                        $siteWideDiscountsPercentageTotal += $discount->getAmount();
                                    } else {
                                        $siteWideDiscountsAmountTotal += $discount->getAmount();
                                    }
                                }
                            }
                        }
                        break;
                    
                    default:
                        break;
                }
            }

            // Apply discount percentage amount
            if ($siteWideDiscountsPercentageTotal){
                if ($siteWideDiscountsPercentageTotal > 50) $siteWideDiscountsPercentageTotal = 50; // Discount percentage cannot go beyond 50%
                $discountAmounts = $total * ($siteWideDiscountsPercentageTotal/100);
                $siteWideDiscountTotal += $discountAmounts;
                $totalDiscounts += $discountAmounts;
                $total -= $discountAmounts;
            }

            // Apply discount amount
            if ($siteWideDiscountsAmountTotal){
                $totalDiscounts += $siteWideDiscountsAmountTotal;
                $siteWideDiscountTotal += $siteWideDiscountsAmountTotal;
                $total -= $siteWideDiscountsAmountTotal;
            }
        // End of applying site wide discounts ====================================


        // Finally work with Coupon codes
        if ($couponCode){
            $coupon = $this->couponRepository->getCoupon($couponCode);
            if ($coupon){
                if ($coupon->getDiscountType() == 'PERCENTAGE'){
                    $couponDiscountTotal = $total * ($coupon->getAmount()/100);
                    $total -= $couponDiscountTotal;
                } else {
                    $couponDiscountTotal = $coupon->getAmount();
                    $total -= $couponDiscountTotal;
                }
            }
        }

        // Total cannot be minus
        if ($total < 0) $total = 0;

        if ($fullDetails){
            return [
                "total" => number_format((float) $total, 2, '.', ''),
                "grandTotal" => number_format((float) $grandTotal, 2, '.', ''),
                "discounts" => [
                    'totalDiscounts' => number_format((float) $totalDiscounts, 2, '.', ''),
                    'categoryWiseDiscounts' => $categoryWiseDiscountTotal,
                    'allCategoryWiseDiscounts' => number_format((float) $allCategoryWiseDiscountTotal, 2, '.', ''),
                    'siteWideDiscounts' => number_format((float) $siteWideDiscountTotal, 2, '.', ''),
                    'couponDiscountTotal' => number_format((float) $couponDiscountTotal, 2, '.', ''),
                    "bookWiseDiscounts" => $detailBookWiseDiscounts
                ]
            ];
        } else {
            return [
                "total" => number_format((float) $total, 2, '.', ''),
                "discounts" => number_format((float) $totalDiscounts, 2, '.', '')
            ];
        }
    }

}
