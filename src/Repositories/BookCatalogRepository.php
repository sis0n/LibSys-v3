<?php

namespace App\Repositories;

use App\Core\Database;

class BookCatalogRepository
{
  private $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getAllBooks(?int $campusId = null)
  {
    $query = "SELECT b.*, c.campus_name 
              FROM books b 
              LEFT JOIN campuses c ON b.campus_id = c.campus_id 
              WHERE b.deleted_at IS NULL";
    $params = [];
    if ($campusId !== null) {
      $query .= " AND b.campus_id = ?";
      $params[] = $campusId;
    }
    $query .= " ORDER BY b.created_at DESC";
    $stmt = $this->db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function getBookById($id)
  {
    $stmt = $this->db->prepare("SELECT b.*, c.campus_name FROM books b LEFT JOIN campuses c ON b.campus_id = c.campus_id WHERE b.book_id = ? AND b.deleted_at IS NULL");
    $stmt->execute([$id]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }

  public function addBook($data)
  {
    $stmt = $this->db->prepare("
            INSERT INTO books 
            (accession_number, call_number, title, author, book_place, book_publisher, year, book_edition, description, book_isbn, book_supplementary, subject, availability, campus_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

    return $stmt->execute([
      $data['accession_number'],
      $data['call_number'],
      $data['title'],
      $data['author'],
      $data['book_place'],
      $data['book_publisher'],
      $data['year'],
      $data['book_edition'],
      $data['description'],
      $data['book_isbn'],
      $data['book_supplementary'] ?? null,
      $data['subject'],
      $data['availability'] ?? 'available',
      $data['campus_id'] ?? null
    ]);
  }

  public function updateBook($id, $data)
  {
    $stmt = $this->db->prepare("
            UPDATE books 
            SET accession_number=?, call_number=?, title=?, author=?, book_place=?, book_publisher=?, year=?, book_edition=?, description=?, book_isbn=?, book_supplementary=?, subject=?, availability=?, campus_id=? 
            WHERE book_id=?
        ");
    return $stmt->execute([
      $data['accession_number'],
      $data['call_number'],
      $data['title'],
      $data['author'],
      $data['book_place'],
      $data['book_publisher'],
      $data['year'],
      $data['book_edition'],
      $data['description'],
      $data['book_isbn'],
      $data['book_supplementary'] ?? null,
      $data['subject'],
      $data['availability'],
      $data['campus_id'] ?? null,
      $id
    ]);
  }

  public function deleteBook($id)
  {
    $stmt = $this->db->prepare("UPDATE books SET deleted_at = NOW() WHERE book_id = ?");
    return $stmt->execute([$id]);
  }

  public function updateAvailability($id, $status)
  {
    $stmt = $this->db->prepare("UPDATE books SET availability = ? WHERE book_id = ? AND deleted_at IS NULL");
    return $stmt->execute([$status, $id]);
  }

  public function searchBooks($keyword)
  {
    $search = "%$keyword%";
    $stmt = $this->db->prepare("
            SELECT b.*, c.campus_name FROM books b 
            LEFT JOIN campuses c ON b.campus_id = c.campus_id
              WHERE (b.title LIKE ? OR b.author LIKE ? OR b.accession_number LIKE ? OR b.subject LIKE ? OR b.book_isbn LIKE ?)
              AND b.deleted_at IS NULL
        ");
    $stmt->execute([$search, $search, $search, $search, $search]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function filterBooks($filters = [])
  {
    $query = "SELECT b.*, c.campus_name FROM books b 
              LEFT JOIN campuses c ON b.campus_id = c.campus_id
              WHERE b.deleted_at IS NULL AND b.availability NOT IN ('lost', 'damaged')";
    $place_holder = [];

    foreach ($filters as $column => $value) {
      $allowed = [
        'subject' => 'b.subject',
        'availability' => 'b.availability',
        'author' => 'b.author',
        'year' => 'b.year',
        'book_publisher' => 'b.book_publisher',
        'campus_id' => 'b.campus_id'
      ];
      if (isset($allowed[$column])) {
        $query .= " AND {$allowed[$column]} = ?";
        $place_holder[] = $value;
      }
    }

    $stmt = $this->db->prepare($query);
    $stmt->execute($place_holder);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function getPaginatedFiltered(
    int $limit,
    int $offset,
    string $search = '',
    string $category = '',
    string $status = '',
    string $sort = 'default',
    ?int $campusId = null
  ): array {
    $limit = max(1, min($limit, 100));
    $offset = max(0, min($offset, 10000));
    $query = "SELECT b.*, c.campus_name 
              FROM books b 
              LEFT JOIN campuses c ON b.campus_id = c.campus_id 
              WHERE b.deleted_at IS NULL AND b.availability NOT IN ('lost', 'damaged')";
    $params = [];

    if ($campusId !== null) {
      $query .= " AND b.campus_id = ?";
      $params[] = $campusId;
    }

    if ($search !== '') {
      $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.book_isbn LIKE ? OR b.accession_number LIKE ?)";
      $searchTerm = "%$search%";
      $params[] = $searchTerm;
      $params[] = $searchTerm;
      $params[] = $searchTerm;
      $params[] = $searchTerm;
    }

    if ($category !== '' && $category !== 'All Categories') {
      $query .= " AND b.subject = ?";
      $params[] = $category;
    }

    if ($status !== '' && strtolower($status) !== 'all status') {
      $query .= " AND b.availability = ?";
      $params[] = strtolower($status);
    }

    $orderBy = "ORDER BY b.created_at DESC";
    switch ($sort) {
      case 'title_asc':
        $orderBy = "ORDER BY b.title ASC";
        break;
      case 'title_desc':
        $orderBy = "ORDER BY b.title DESC";
        break;
      case 'year_asc':
        $orderBy = "ORDER BY b.year ASC, b.title ASC";
        break;
      case 'year_desc':
        $orderBy = "ORDER BY b.year DESC, b.title ASC";
        break;
    }

    $query .= " " . $orderBy . " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $stmt = $this->db->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function countAvailableBooks(?int $campusId = null): int
  {
    $query = "SELECT COUNT(*) FROM books WHERE availability = 'available' AND deleted_at IS NULL";
    $params = [];
    if ($campusId !== null) {
        $query .= " AND campus_id = ?";
        $params[] = $campusId;
    }
    $stmt = $this->db->prepare($query);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
  }

  public function countPaginatedFiltered(
    string $search = '',
    string $category = '',
    string $status = '',
    ?int $campusId = null
  ): int {
    $query = "SELECT COUNT(*) FROM books WHERE deleted_at IS NULL AND availability NOT IN ('lost', 'damaged')";
    $params = [];

    if ($campusId !== null) {
        $query .= " AND campus_id = ?";
        $params[] = $campusId;
    }

    if ($search !== '') {
      $query .= " AND (title LIKE ? OR author LIKE ? OR book_isbn LIKE ? OR accession_number LIKE ?)";
      $searchTerm = "%$search%";
      $params[] = $searchTerm;
      $params[] = $searchTerm;
      $params[] = $searchTerm;
      $params[] = $searchTerm;
    }

    if ($category !== '' && $category !== 'All Categories') {
      $query .= " AND subject = ?";
      $params[] = $category;
    }

    if ($status !== '' && $status !== 'All Status') {
      $query .= " AND availability = ?";
      $params[] = strtolower($status);
    }

    $stmt = $this->db->prepare($query);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
  }
}
