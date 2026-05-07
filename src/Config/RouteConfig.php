<?php

namespace App\Config;

use App\Core\Router;
use App\Controllers\ViewController;
use App\Controllers\DomPdfTemplateController;

class RouteConfig
{
  public static function register(): Router
  {
    $router = new Router();

    $router->get('', 'ViewController@handleLandingPage');
    $router->get('login', 'AuthController@showLogin');
    $router->get('scanner/attendance', 'ScannerController@scannerDisplay', ['scanner']);

    $router->get('forgotPassword', 'ForgotPasswordController@index');
    $router->post('forgot-password/send-otp', 'ForgotPasswordController@sendOTP');

    $router->get('verifyOTP', 'ForgotPasswordController@verifyOTPPage');
    $router->post('verify-otp/check', 'ForgotPasswordController@checkOTP');
    $router->post('verify-otp/resend', 'ForgotPasswordController@resendOTP');

    $router->get('resetPassword', 'ForgotPasswordController@resetPasswordPage');
    $router->post('reset-password/submit', 'ForgotPasswordController@updatePassword');

    $router->post('login', 'AuthController@login');
    $router->post('logout', 'AuthController@logout');
    $router->post('api/change-password', 'AuthController@changePassword');

    $router->post('api/scanner/scan', 'ScannerController@attendance', ['scanner']);
    $router->post('api/scanner/manual', 'ScannerController@manual', ['scanner']);

    $router->get('api/guest/fetchBooks', 'GuestController@fetchGuestBooks');

    // Unified Catalog API
    $router->get('api/bookCatalog/availableCount', 'BookCatalogController@getAvailableCount', ['student', 'faculty', 'staff']);
    $router->get('api/bookCatalog/fetch', 'BookCatalogController@fetch', ['student', 'faculty', 'staff']);

    // Unified Cart API
    $router->get('api/cart', 'CartController@index', ['student', 'faculty', 'staff']);
    $router->get('api/cart/add/{id}', 'CartController@add', ['student', 'faculty', 'staff']);
    $router->post('api/cart/remove/{id}', 'CartController@remove', ['student', 'faculty', 'staff']);
    $router->post('api/cart/clear', 'CartController@clearCart', ['student', 'faculty', 'staff']);
    $router->get('api/cart/json', 'CartController@getCartJson', ['student', 'faculty', 'staff']);
    $router->post('api/cart/checkout', 'CartController@checkout', ['student', 'faculty', 'staff']);

    // Unified Borrowing History API
    $router->get('api/borrowing-history/pagination', 'BorrowingHistoryController@fetchPaginatedBorrowingHistory', ['student', 'faculty', 'staff']);
    $router->get('api/borrowing-history/stats', 'BorrowingHistoryController@fetchStats', ['student', 'faculty', 'staff']);

    // Unified Book Catalog Route
    $router->get('bookCatalog', 'BookCatalogController@index', ['faculty', 'staff', 'student']);

    // =========================================================================
    // UNIFIED MANAGEMENT API ROUTES (Smart Unified Management)
    // =========================================================================
    
    // Profile API
    $router->get('api/profile/get', 'UserProfileController@getProfile');
    $router->post('api/profile/update', 'UserProfileController@updateProfile');

    // Book Management
    $router->get('api/bookManagement/fetch', 'BookManagementController@fetch', ['book management']);
    $router->get('api/bookManagement/get/{id}', 'BookManagementController@getDetails', ['book management']);
    $router->get('api/bookManagement/details/{id}', 'BookManagementController@getDetails', ['book management']);
    $router->post('api/bookManagement/store', 'BookManagementController@store', ['book management']);
    $router->post('api/bookManagement/add', 'BookManagementController@store', ['book management']);
    $router->post('api/bookManagement/update/{id}', 'BookManagementController@update', ['book management']);
    $router->post('api/bookManagement/delete/{id}', 'BookManagementController@destroy', ['book management']);
    $router->post('api/bookManagement/destroy/{id}', 'BookManagementController@destroy', ['book management']);
    $router->post('api/bookManagement/reactivate/{id}', 'BookManagementController@reactivate', ['book management']);
    $router->post('api/bookManagement/deleteMultiple', 'BookManagementController@deleteMultiple', ['book management']);
    $router->post('api/bookManagement/delete-multiple', 'BookManagementController@deleteMultiple', ['book management']);
    $router->post('api/bookManagement/bulkImport', 'BookManagementController@bulkImport', ['book management']);
    $router->post('api/bookManagement/bulk-import', 'BookManagementController@bulkImport', ['book management']);
    $router->get('api/bookManagement/history/{id}', 'BookManagementController@getBookBorrowingHistory', ['book management']);

    // Equipment Management
    $router->get('api/equipmentManagement/fetch', 'EquipmentManagementController@fetch', ['equipment management']);
    $router->get('api/equipmentManagement/get/{id}', 'EquipmentManagementController@get', ['equipment management']);
    $router->post('api/equipmentManagement/store', 'EquipmentManagementController@store', ['equipment management']);
    $router->post('api/equipmentManagement/update/{id}', 'EquipmentManagementController@update', ['equipment management']);
    $router->post('api/equipmentManagement/toggleActive/{id}', 'EquipmentManagementController@toggleActive', ['equipment management']);
    $router->post('api/equipmentManagement/delete/{id}', 'EquipmentManagementController@destroy', ['equipment management']);
    $router->post('api/equipmentManagement/destroy/{id}', 'EquipmentManagementController@destroy', ['equipment management']);
    $router->get('api/equipmentManagement/delete-multiple', 'EquipmentManagementController@deleteMultiple', ['equipment management']);

    // User Management
    $router->get('api/userManagement/pagination', 'UserManagementController@fetchPaginatedUsers', ['user management']);
    $router->get('api/userManagement/get/{id}', 'UserManagementController@getUserById', ['user management']);
    $router->get('api/userManagement/search', 'UserManagementController@search', ['user management']);
    $router->post('api/userManagement/add', 'UserManagementController@addUser', ['user management']);
    $router->post('api/userManagement/update/{id}', 'UserManagementController@updateUser', ['user management']);
    $router->post('api/userManagement/delete/{id}', 'UserManagementController@deleteUser', ['user management']);
    $router->post('api/userManagement/deleteMultiple', 'UserManagementController@deleteMultipleUsers', ['user management']);
    $router->post('api/userManagement/toggleStatus/{id}', 'UserManagementController@toggleStatus', ['user management']);
    $router->post('api/userManagement/allowEdit/{id}', 'UserManagementController@allowEdit', ['user management']);
    $router->post('api/userManagement/allowMultipleEdit', 'UserManagementController@allowMultipleEdit', ['user management']);
    $router->post('api/userManagement/bulkImport', 'UserManagementController@bulkImport', ['user management']);

    // QR Scanner
    $router->post('api/qrScanner/scanTicket', 'QRScannerController@scan', ['qr scanner']);
    $router->post('api/qrScanner/borrowTransaction', 'QRScannerController@borrowTransaction', ['qr scanner']);
    $router->get('api/qrScanner/transactionHistory', 'QRScannerController@history', ['qr scanner']);

    // Returning
    $router->get('api/returning/getTableData', 'ReturningController@getOverdue', ['returning']);
    $router->get('api/returning/getRecent', 'ReturningController@getRecentReturnsJson', ['returning']);
    $router->post('api/returning/checkBook', 'ReturningController@checkBookStatus', ['returning']);
    $router->post('api/returning/markReturned', 'ReturningController@returnBook', ['returning']);
    $router->post('api/returning/extend', 'ReturningController@extendDueDate', ['returning']);
    $router->post('api/returning/sendOverdueEmail', 'ReturningController@sendOverdueEmail', ['returning']);

    // Manual Borrowing (Borrowing Form)
    $router->get('api/borrowingForm/manualBorrow', 'ManualBorrowingController@manualBorrow', ['borrowing form']);
    $router->post('api/borrowingForm/checkUser', 'ManualBorrowingController@checkUser', ['borrowing form']);
    $router->post('api/borrowingForm/create', 'ManualBorrowingController@create', ['borrowing form']);
    $router->get('api/borrowingForm/getEquipments', 'ManualBorrowingController@getEquipments', ['borrowing form']);
    $router->get('api/borrowingForm/getCollaterals', 'ManualBorrowingController@getCollaterals', ['borrowing form']);

    // Overdue Tracking
    $router->get('api/overdue/getTableData', 'OverdueController@getTableData', ['overdue tracking']);
    $router->post('api/overdue/sendReminder', 'OverdueController@sendReminder', ['overdue tracking']);

    // Dashboard & History
    $router->get('api/dashboard/getData', 'DashboardController@getData', ['reports', 'superadmin']);
    $router->get('api/transactionHistory/getTableData', 'TransactionHistoryController@getTableData', ['transaction history']);

    // Reports
    $router->get('api/reports/circulated-books', 'ReportController@getCirculatedBooksReport', ['reports']);
    $router->get('api/reports/circulated-equipments', 'ReportController@getCirculatedEquipmentsReport', ['reports']);
    $router->get('api/reports/top-visitors', 'ReportController@getTopVisitors', ['reports']);
    $router->get('api/reports/top-borrowers', 'ReportController@getTopBorrowers', ['reports']);
    $router->get('api/reports/most-borrowed-books', 'ReportController@getMostBorrowedBooks', ['reports']);
    $router->get('api/reports/deleted-books', 'ReportController@getDeletedBooks', ['reports']);
    $router->get('api/reports/lost-damaged-books', 'ReportController@getLostDamagedBooksReport', ['reports']);
    $router->get('api/reports/library-visits-department', 'ReportController@getLibraryVisitsByDepartment', ['reports']);
    $router->get('api/reports/getActivityReport', 'ReportController@getActivityReport', ['reports']);
    $router->get('api/reports/getGraphData', 'ReportController@getReportGraphData', ['reports']);
    $router->post('api/reports/generate-report', 'DomPdfTemplateController@generateLibraryReport', ['reports']);

    // Attendance
    $router->get('api/attendance/logs/ajax', 'AttendanceController@fetchLogsAjax', ['attendance logs']);

    // =========================================================================
    // SUPERADMIN ONLY ROUTES
    // =========================================================================
    $router->get('api/superadmin/libraryPolicies/getAll', 'LibraryPolicyController@getAll', ['superadmin']);
    $router->post('api/superadmin/libraryPolicies/update', 'LibraryPolicyController@update', ['superadmin']);
    $router->get('api/campuses/active', 'CampusController@getActive', ['superadmin', 'admin', 'librarian']);
    $router->get('api/superadmin/campuses/fetch', 'CampusManagementController@fetch', ['superadmin']);
    $router->post('api/superadmin/campuses/store', 'CampusManagementController@store', ['superadmin']);
    $router->post('api/superadmin/campuses/update/{id}', 'CampusManagementController@update', ['superadmin']);
    $router->post('api/superadmin/campuses/toggleStatus/{id}', 'CampusManagementController@toggleStatus', ['superadmin']);
    $router->post('api/superadmin/campuses/delete/{id}', 'CampusManagementController@destroy', ['superadmin']);
    $router->get('api/superadmin/auditLogs/fetch', 'AuditLogController@fetch', ['superadmin']);
    $router->get('api/superadmin/studentPromotion/fetch', 'StudentPromotionController@fetch', ['superadmin']);
    $router->post('api/superadmin/studentPromotion/promote', 'StudentPromotionController@promote', ['superadmin']);
    $router->post('api/superadmin/studentPromotion/deactivate', 'StudentPromotionController@deactivate', ['superadmin']);
    $router->post('api/superadmin/studentPromotion/activate', 'StudentPromotionController@activate', ['superadmin']);
    
    // Backup API
    $router->get('api/superadmin/backup/logs', 'BackupController@listBackupLogs', ['superadmin']);
    $router->get('api/superadmin/backup/database/full', 'BackupController@initiateBackup', ['superadmin']);
    $router->get('api/superadmin/backup/export/zip/{tableName}', 'BackupController@exportBothFormats', ['superadmin']);
    $router->get('api/superadmin/backup/secure_download/{filename}', 'BackupController@downloadBackup', ['superadmin']);
    $router->post('api/superadmin/backup/delete/{filename}', 'BackupController@deleteBackup', ['superadmin']);
    $router->post('api/superadmin/backup/restore/{filename}', 'BackupController@restoreBackup', ['superadmin']);
    $router->post('api/superadmin/backup/upload_restore', 'BackupController@uploadAndRestore', ['superadmin']);

    $router->get('api/campuses/all', 'DataController@getAllCampuses');
    $router->get('api/data/getAllCourses', 'DataController@getAllCourses');
    $router->get('api/data/getColleges', 'DataController@getColleges');

    // =========================================================================
    // VIEW ROUTES (UI)
    // =========================================================================
    $router->get('dashboard', 'ViewController@handleDashboard');
    $router->get('userManagement', 'UserManagementController@index', ['user management']);
    $router->get('bookManagement', 'BookManagementController@index', ['book management']);
    $router->get('equipmentManagement', 'EquipmentManagementController@index', ['equipment management']);
    $router->get('borrowingForm', 'ManualBorrowingController@index', ['borrowing form']);
    $router->get('qrScanner', 'QRScannerController@index', ['qr scanner']);
    $router->get('returning', 'ReturningController@index', ['returning']);
    $router->get('transactionHistory', 'TransactionHistoryController@index', ['transaction history']);
    $router->get('attendanceLogs', 'AttendanceController@index', ['attendance logs']);
    $router->get('overdue', 'OverdueController@index', ['overdue tracking']);
    $router->get('reports', 'ReportController@index', ['reports']);
    $router->get('auditLogs', 'AuditLogController@index', ['superadmin', 'admin']);
    $router->get('campusManagement', 'CampusManagementController@index', ['superadmin']);
    $router->get('studentPromotion', 'StudentPromotionController@index', ['superadmin']);
    $router->get('restoreUser', 'RestoreUserController@index', ['restore users']);
    $router->get('api/restoreUser/fetch', 'RestoreUserController@getDeletedUsersJson', ['restore users']);
    $router->post('api/restoreUser/restore', 'RestoreUserController@restore', ['restore users']);
    $router->post('api/restoreUser/delete/{id}', 'RestoreUserController@archive', ['restore users']);
    
    // Profile & Password Views
    $router->get('myProfile', 'UserProfileController@index');
    $router->get('changePassword', 'UserProfileController@changePasswordPage');

    $router->get('myAttendance', 'AttendanceController@myAttendance', ['student']);
    $router->get('borrowingHistory', 'BorrowingHistoryController@index', ['student', 'faculty', 'staff']);

    // Fallback
    $router->get('{action}', 'ViewController@handleGenericPage');
    $router->get('{action}/{id}', 'ViewController@handleGenericPage');

    return $router;
  }
}
