<?php

namespace App\Controller;

use App\Repository\BookCategoryRepository;
use App\Repository\BookRepository;
use App\Repository\CartRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\Query;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    public function __construct(BookRepository $bookRepository, 
        CategoryRepository $categoryRepository, 
        BookCategoryRepository $bookCategoryRepository,
        CartRepository $cartRepository
    ) {
        $this->bookRepository = $bookRepository;
        $this->categoryRepository = $categoryRepository;
        $this->bookCategoryRepository = $bookCategoryRepository;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @Route("/api/books", name="get_all_book", methods={"GET"}, format="json")
     */
    public function getAllBooks(): Response
    {
        $bookResponce = [];

        // Get all books
        $books = $this->bookRepository->findAll(); //createQueryBuilder('c')->getQuery()->getResult(Query::HYDRATE_ARRAY);
        
        // Loop through books to make an array and fill category data
        foreach ($books as $book) {
            $bookRes = $book->toArray();
            
            $categories = [];

            $bookCategories = $this->bookCategoryRepository->findBy(['book' => $book->getId()]);
            foreach ($bookCategories as $bookCat) {
                $catData = new stdClass();

                // Get category object
                $catObj = $bookCat->getCategory();

                $catData->id = $catObj->getId();
                $catData->name = $catObj->getName();
                
                // Set category data
                $categories[] = $catData;
            }

            // Set all categories
            $bookRes['categories'] = $categories;

            $bookResponce[] = $bookRes;
        }

        return $this->json($bookResponce);
    }


    /**
     * @Route("/api/books/category/{id}", name="get_all_book_by_category", methods={"GET"}, format="json")
     */
    public function getAllBooksByCategory($id): Response
    {
        $bookResponce = [];

        // Get category
        $category = $this->categoryRepository->findBy(['id' => $id]);
        if ($category){

            $categoryBooks = $this->bookCategoryRepository->findBy(['category' => $id]);

            // Loop through books to make an array and fill category data
            foreach ($categoryBooks as $catBook) {

                $book = $catBook->getBook();
                $bookRes = $book->toArray();
                
                $categories = [];

                $bookCategories = $this->bookCategoryRepository->findBy(['book' => $book->getId()]);
                foreach ($bookCategories as $bookCat) {
                    $catData = new stdClass();

                    // Get category object
                    $catObj = $bookCat->getCategory();

                    $catData->id = $catObj->getId();
                    $catData->name = $catObj->getName();
                    
                    // Set category data
                    $categories[] = $catData;
                }

                // Set all categories
                $bookRes['categories'] = $categories;

                $bookResponce[] = $bookRes;
            }

            return $this->json($bookResponce);
        } else {
            throw new NotFoundHttpException("Category not found.");
        }

    }

    /**
     * @Route("/api/cart/add", name="add_to_cart", methods={"POST"}, format="json")
     */
    public function addToCart(Request $request, $id): Response
    {
        // Load data from request 
        $data = json_decode($request->getContent(), true);

        if ($data['id']){
            // Get book
            $book = $this->bookRepository->findOneBy(['id' => $data['id']]);
            if ($book){
                $this->cartRepository->addToCart($book);
                
                return $this->json([
                    'status' => 'success'
                ]);
            } else {
                throw new NotFoundHttpException("Book not sent.");
            }
        } else {
            throw new NotFoundHttpException("Book id not sent.");
        }
    }
}
