<?php

namespace App\Controller;

use App\Repository\BookCategoryRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends BaseController
{
    public function __construct(BookRepository $bookRepository, 
        CategoryRepository $categoryRepository, 
        BookCategoryRepository $bookCategoryRepository
    )
    {
        $this->bookRepository = $bookRepository;
        $this->categoryRepository = $categoryRepository;
        $this->bookCategoryRepository = $bookCategoryRepository;
    }

    /**
     * @Route("/api/books", name="get_all_book", methods={"GET"}, format="json")
     */
    public function getAllBooks(): Response
    {
        $bookResponse = [];

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

            $bookResponse[] = $bookRes;
        }

        return $this->response($bookResponse);
    }


    /**
     * @Route("/api/books/category/{id}", name="get_all_book_by_category", methods={"GET"}, format="json")
     */
    public function getAllBooksByCategory($id): Response
    {
        $bookResponse = [];

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

                $bookResponse[] = $bookRes;
            }

            return $this->response($bookResponse);
        } else {
            return $this->response("Category not found.", 'error');
        }

    }

}
