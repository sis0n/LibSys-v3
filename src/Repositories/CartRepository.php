<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CartRepository
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function addToCart(int $userId, int $bookId): bool
  {
    $checkBook = $this->db->prepare("SELECT availability FROM books WHERE book_id = ?");
    $checkBook->execute([$bookId]);
    $book = $checkBook->fetch(PDO::FETCH_ASSOC);

    if (!$book || $book['availability'] !== 'available') {
      return false; 
    }

    $checkDuplicate = $this->db->prepare("SELECT 1 FROM carts WHERE user_id = ? AND book_id = ?");
    $checkDuplicate->execute([$userId, $bookId]);
    if ($checkDuplicate->fetch()) {
      return false;
    }

    $stmt = $this->db->prepare("INSERT INTO carts (user_id, book_id) VALUES (?, ?)");
    return $stmt->execute([$userId, $bookId]);
  }

  public function getCartByUser(int $userId): array
  {
    $sql = "SELECT c.cart_id, b.book_id, b.title, b.author, b.accession_number, b.subject, b.call_number
                FROM carts c
                INNER JOIN books b ON c.book_id = b.book_id
                WHERE c.user_id = :user_id
                ORDER BY c.cart_id DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(fn($row) => [
      "id" => (int)$row["book_id"],
      "cart_id" => (int)$row["cart_id"],
      "title" => $row["title"],
      "author" => $row["author"],
      "accessionNumber" => $row["accession_number"],
      "subject" => $row["subject"],
      "callNumber" => $row["call_number"],
      "type" => "book",
      "icon" => "ph-book"
    ], $rows);
  }

  public function removeFromCart(int $cartId, int $userId): bool
  {
    $stmt = $this->db->prepare("DELETE FROM carts WHERE cart_id = ? AND user_id = ?");
    return $stmt->execute([$cartId, $userId]);
  }

  public function countCartItems(int $userId): int
  {
    $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM carts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return isset($row['total']) ? (int)$row['total'] : 0;
  }

  public function clearCart(int $userId): bool
  {
    $stmt = $this->db->prepare("DELETE FROM carts WHERE user_id = ?");
    return $stmt->execute([$userId]);
  }
}
