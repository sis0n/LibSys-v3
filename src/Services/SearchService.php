<?php

namespace App\Services;

use App\Repositories\BookCatalogRepository;
use App\Repositories\CampusRepository;

class SearchService
{
    private BookCatalogRepository $bookRepo;
    private CampusRepository $campusRepo;

    public function __construct()
    {
        $this->bookRepo = new BookCatalogRepository();
        $this->campusRepo = new CampusRepository();
    }

    /**
     * Search books with pagination and filters
     */
    public function searchBooks(array $params, ?int $userCampusId): array
    {
        $search = $params['search'] ?? '';
        $offset = (int)($params['offset'] ?? 0);
        $limit = (int)($params['limit'] ?? 30);
        $category = $params['category'] ?? '';
        $status = $params['status'] ?? '';
        $sort = $params['sort'] ?? 'default';
        
        $campusIdParam = $params['campus_id'] ?? null;
        $campusId = null;

        if ($campusIdParam === 'all') {
            $campusId = null;
        } elseif ($campusIdParam !== null) {
            $campusId = (int)$campusIdParam;
        } else {
            $campusId = $userCampusId;
        }

        $books = $this->bookRepo->getPaginatedFiltered($limit, $offset, $search, $category, $status, $sort, $campusId);
        $totalCount = $this->bookRepo->countPaginatedFiltered($search, $category, $status, $campusId);

        // Transform paths
        $books = array_map(function($book) {
            if (!empty($book['cover'])) {
                $book['cover'] = \BASE_URL . '/' . ltrim($book['cover'], '/');
            }
            return $book;
        }, $books);

        return [
            'books' => $books,
            'totalCount' => $totalCount
        ];
    }

    /**
     * Get list of all campuses for filter dropdown
     */
    public function getFilterCampuses(): array
    {
        return $this->campusRepo->getAllCampuses();
    }
}
