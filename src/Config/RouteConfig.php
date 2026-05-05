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

    // Faculty API
    $router->get('api/faculty/attendance/get', 'AttendanceController@getMyAttendance', ['faculty']);
    $router->get('api/faculty/qrBorrowingTicket/checkStatus', 'TicketController@checkStatus');
    $router->get('api/faculty/bookCatalog/availableCount', 'FacultyBookCatalogController@getAvailableCount', ['faculty']);
    $router->get('api/faculty/bookCatalog/fetch', 'FacultyBookCatalogController@fetch', ['faculty']);
    $router->get('api/faculty/cart', 'CartController@index', ['faculty']);
    $router->get('api/faculty/cart/add/{id}', 'CartController@add', ['faculty']);
    $router->post('api/faculty/cart/remove/{id}', 'CartController@remove', ['faculty']);
    $router->post('api/faculty/cart/clear', 'CartController@clearCart', ['faculty']);
    $router->get('api/faculty/cart/json', 'CartController@getCartJson', ['faculty']);
    $router->post('api/faculty/cart/checkout', 'CartController@checkout', ['faculty']);
    $router->get('api/faculty/qrBorrowingTicket', 'TicketController@index', ['faculty']);
    $router->get('api/faculty/borrowing-history/pagination', 'FacultyBorrowingHistoryController@fetchPaginatedBorrowingHistory', ['faculty']);
    $router->get('api/faculty/borrowing-history/stats', 'FacultyBorrowingHistoryController@fetchStats', ['faculty']);
    $router->get('api/data/getColleges', 'DataController@getColleges', ['faculty']);

    // Staff API
    $router->get('api/staff/attendance/get', 'AttendanceController@getMyAttendance', ['staff']);
    $router->get('api/staff/qrBorrowingTicket/checkStatus', 'TicketController@checkStatus', ['staff']);
    $router->get('api/staff/bookCatalog/availableCount', 'StaffBookCatalogController@getAvailableCount', ['staff']);
    $router->get('api/staff/bookCatalog/fetch', 'StaffBookCatalogController@fetch', ['staff']);
    $router->get('api/staff/cart', 'CartController@index', ['staff']);
    $router->get('api/staff/cart/add/{id}', 'CartController@add', ['staff']);
    $router->post('api/staff/cart/remove/{id}', 'CartController@remove', ['staff']);
    $router->post('api/staff/cart/clear', 'CartController@clearCart', ['staff']);
    $router->get('api/staff/cart/json', 'CartController@getCartJson', ['staff']);
    $router->post('api/staff/cart/checkout', 'CartController@checkout', ['staff']);
    $router->get('api/staff/qrBorrowingTicket', 'TicketController@index', ['staff']);
    $router->get('api/staff/borrowing-history/pagination', 'StaffBorrowingHistoryController@fetchPaginatedBorrowingHistory', ['staff']);
    $router->get('api/staff/borrowing-history/stats', 'StaffBorrowingHistoryController@fetchStats', ['staff']);

    // Student API
    $router->get('api/student/attendance/get', 'AttendanceController@getMyAttendance', ['student']);
    $router->get('api/student/cart', 'CartController@index', ['student']);
    $router->get('api/student/cart/add/{id}', 'CartController@add', ['student']);
    $router->post('api/student/cart/remove/{id}', 'CartController@remove', ['student']);
    $router->post('api/student/cart/clear', 'CartController@clearCart', ['student']);
    $router->get('api/student/cart/json', 'CartController@getCartJson', ['student']);
    $router->post('api/student/cart/checkout', 'CartController@checkout', ['student']);
    $router->get('api/student/qrBorrowingTicket/checkStatus', 'TicketController@checkStatus');
    $router->get('api/student/bookCatalog/availableCount', 'BookCatalogController@getAvailableCount', ['student']);
    $router->get('api/student/bookCatalog/fetch', 'BookCatalogController@fetch', ['student']);
    $router->get('api/student/borrowingHistory/fetch', 'StudentBorrowingHistoryController@fetchHistory', ['student']);
    $router->get('api/student/borrowing-history/stats', 'StudentBorrowingHistoryController@fetchStats', ['student']);
    $router->get('api/student/borrowing-history/pagination', 'StudentBorrowingHistoryController@fetchPaginatedBorrowingHistory', ['student']);

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

    // Bulk Delete
    $router->get('bulkDeleteQueue', 'BulkDeleteController@index', ['bulk delete queue']);
    $router->get('api/bulk-delete/pending', 'BulkDeleteController@fetchPending', ['bulk delete queue']);
    $router->get('api/bulk-delete/get/{id}', 'BulkDeleteController@getDetails', ['bulk delete queue']);
    $router->post('api/bulk-delete/approve', 'BulkDeleteController@approve', ['bulk delete queue']);
    $router->post('api/bulk-delete/reject', 'BulkDeleteController@reject', ['bulk delete queue']);

    // Fallback
    $router->get('{action}', 'ViewController@handleGenericPage');
    $router->get('{action}/{id}', 'ViewController@handleGenericPage');

    return $router;
  }
}
