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

    $router->get('api/faculty/attendance/get', 'AttendanceController@getMyAttendance', ['faculty']);
    $router->get('api/faculty/qrBorrowingTicket/checkStatus', 'FacultyTicketController@checkStatus');
    $router->get('api/faculty/bookCatalog/availableCount', 'FacultyBookCatalogController@getAvailableCount', ['faculty']);
    $router->get('api/faculty/bookCatalog/fetch', 'FacultyBookCatalogController@fetch', ['faculty']);
    $router->get('api/faculty/cart', 'FacultyCartController@index', ['faculty']);
    $router->get('api/faculty/cart/add/{id}', 'FacultyCartController@add', ['faculty']);
    $router->post('api/faculty/cart/remove/{id}', 'FacultyCartController@remove', ['faculty']);
    $router->post('api/faculty/cart/clear', 'FacultyCartController@clearCart', ['faculty']);
    $router->get('api/faculty/cart/json', 'FacultyCartController@getCartJson', ['faculty']);
    $router->post('api/faculty/cart/checkout', 'FacultyTicketController@checkout', ['faculty']);
    $router->get('api/faculty/qrBorrowingTicket', 'FacultyTicketController@show', ['faculty']);
    $router->get('api/faculty/myprofile/get', 'FacultyProfileController@getProfile', ['faculty']);
    $router->post('api/faculty/myprofile/update', 'FacultyProfileController@updateProfile', ['faculty']);
    $router->get('api/faculty/borrowing-history/pagination', 'FacultyBorrowingHistoryController@fetchPaginatedBorrowingHistory', ['faculty']);
    $router->get('api/faculty/borrowing-history/stats', 'FacultyBorrowingHistoryController@fetchStats', ['faculty']);
    $router->get('api/data/getColleges', 'DataController@getColleges', ['faculty']);

    $router->get('api/staff/attendance/get', 'AttendanceController@getMyAttendance', ['staff']);
    $router->get('api/staff/qrBorrowingTicket/checkStatus', 'StaffTicketController@checkStatus', ['staff']);
    $router->get('api/staff/bookCatalog/availableCount', 'StaffBookCatalogController@getAvailableCount', ['staff']);
    $router->get('api/staff/bookCatalog/fetch', 'StaffBookCatalogController@fetch', ['staff']);
    $router->get('api/staff/cart', 'StaffCartController@index', ['staff']);
    $router->get('api/staff/cart/add/{id}', 'StaffCartController@add', ['staff']);
    $router->post('api/staff/cart/remove/{id}', 'StaffCartController@remove', ['staff']);
    $router->post('api/staff/cart/clear', 'StaffCartController@clearCart', ['staff']);
    $router->get('api/staff/cart/json', 'StaffCartController@getCartJson', ['staff']);
    $router->post('api/staff/cart/checkout', 'StaffTicketController@checkout', ['staff']);
    $router->get('api/staff/qrBorrowingTicket', 'StaffTicketController@show', ['staff']);
    $router->get('api/staff/myprofile/get', 'StaffProfileController@getProfile', ['staff']);
    $router->post('api/staff/myprofile/update', 'StaffProfileController@updateProfile', ['staff']);
    $router->get('api/staff/borrowing-history/pagination', 'StaffBorrowingHistoryController@fetchPaginatedBorrowingHistory', ['staff']);
    $router->get('api/staff/borrowing-history/stats', 'StaffBorrowingHistoryController@fetchStats', ['staff']);

    $router->get('api/librarian/restoreBooks/fetch', 'RestoreBookController@getDeletedBooksJson', ['restore books']);
    $router->post('api/librarian/restoreBooks/restore', 'RestoreBookController@restore', ['restore books']);
    $router->post('api/librarian/restoreBooks/archive/{id}', 'RestoreBookController@archiveBookAction', ['restore books']);
    $router->get('api/librarian/booksmanagement/fetch', 'BookManagementController@fetch', ['book management']);
    $router->get('api/librarian/booksmanagement/get/{id}', 'BookManagementController@getDetails', ['book management']);
    $router->post('api/librarian/booksmanagement/store', 'BookManagementController@store', ['book management']);
    $router->post('api/librarian/booksmanagement/update/{id}', 'BookManagementController@update', ['book management']);
    $router->post('api/librarian/booksmanagement/delete/{id}', 'BookManagementController@destroy', ['book management']);
    $router->post('api/librarian/booksmanagement/deleteMultiple', 'BookManagementController@deleteMultiple', ['book management']);
    $router->post('api/librarian/booksmanagement/bulkImport', 'BookManagementController@bulkImport', ['book management']);
    $router->get('api/librarian/booksmanagement/history/{id}', 'BookManagementController@getBorrowingHistory', ['book management']);
    $router->get('api/librarian/equipmentManagement/fetch', 'EquipmentManagementController@fetch', ['equipment management']);
    $router->get('api/librarian/equipmentManagement/get/{id}', 'EquipmentManagementController@get', ['equipment management']);
    $router->post('api/librarian/equipmentManagement/store', 'EquipmentManagementController@store', ['equipment management']);
    $router->post('api/librarian/equipmentManagement/update/{id}', 'EquipmentManagementController@update', ['equipment management']);
    $router->post('api/librarian/equipmentManagement/toggleActive/{id}', 'EquipmentManagementController@toggleActive', ['equipment management']);
    $router->post('api/librarian/qrScanner/scanTicket', 'QRScannerController@scan', ['qr scanner']);
    $router->post('api/librarian/qrScanner/borrowTransaction', 'QRScannerController@borrowTransaction', ['qr scanner']);
    $router->get('api/librarian/returning/getTableData', 'ReturningController@getOverdue', ['returning']);
    $router->get('api/librarian/returning/getRecent', 'ReturningController@getRecentReturnsJson', ['returning']);
    $router->post('api/librarian/returning/checkBook', 'ReturningController@checkBookStatus', ['returning']);
    $router->post('api/librarian/returning/markReturned', 'ReturningController@returnBook', ['returning']);
    $router->post('api/librarian/returning/extend', 'ReturningController@extendDueDate', ['returning']);
    $router->post('api/librarian/returning/sendOverdueEmail', 'ReturningController@sendOverdueEmail', ['returning']);
    $router->get('api/librarian/transactionHistory/json', 'TransactionHistoryController@getTransactionsJson', ['transaction history']);
    $router->get('api/librarian/reports/circulated-books', 'ReportController@getCirculatedBooksReport', ['reports']);
    $router->get('api/librarian/reports/circulated-equipments', 'ReportController@getCirculatedEquipmentsReport', ['reports']);
    $router->get('api/librarian/reports/top-visitors', 'ReportController@getTopVisitors', ['reports']);
    $router->get('api/librarian/reports/top-borrowers', 'ReportController@getTopBorrowers', ['reports']);
    $router->get('api/librarian/reports/most-borrowed-books', 'ReportController@getMostBorrowedBooks', ['reports']);
    $router->get('api/librarian/reports/deleted-books', 'ReportController@getDeletedBooks', ['reports']);
    $router->get('api/librarian/reports/library-visits-department', 'ReportController@getLibraryVisitsByDepartment', ['reports']);
    $router->get('api/librarian/reports/getGraphData', 'ReportController@getReportGraphData', ['reports']);
    $router->get('api/librarian/borrowingForm/manualBorrow', 'ManualBorrowingController@manualBorrow', ['borrowing form']);
    $router->post('api/librarian/borrowingForm/checkUser', 'ManualBorrowingController@checkUser', ['borrowing form']);
    $router->post('api/librarian/borrowingForm/create', 'ManualBorrowingController@create', ['borrowing form']);
    $router->get('api/librarian/borrowingForm/getEquipments', 'ManualBorrowingController@getEquipments', ['borrowing form']);
    $router->get('api/librarian/borrowingForm/getCollaterals', 'ManualBorrowingController@getCollaterals', ['borrowing form']);
    $router->post('api/librarian/reports/generate-report', 'DomPdfTemplateController@generateLibraryReport', ['reports']);
    $router->get('api/librarian/dashboard/getData', 'DashboardController@getData', ['reports']);


    $router->get('api/admin/restoreBooks/fetch', 'RestoreBookController@getDeletedBooksJson', ['restore books']);
    $router->post('api/admin/restoreBooks/restore', 'RestoreBookController@restore', ['restore books']);
    $router->post('api/admin/restoreBooks/archive/{id}', 'RestoreBookController@archiveBookAction', ['restore books']);
    $router->get('api/admin/bookManagement/fetch', 'BookManagementController@fetch', ['book management']);
    $router->get('api/admin/bookManagement/get/{id}', 'BookManagementController@getDetails', ['book management']);
    $router->post('api/admin/bookManagement/store', 'BookManagementController@store', ['book management']);
    $router->post('api/admin/bookManagement/update/{id}', 'BookManagementController@update', ['book management']);
    $router->post('api/admin/bookManagement/delete/{id}', 'BookManagementController@destroy', ['book management']);
    $router->post('api/admin/bookManagement/deleteMultiple', 'BookManagementController@deleteMultiple', ['book management']);
    $router->post('api/admin/bookManagement/bulkImport', 'BookManagementController@bulkImport', ['book management']);
    $router->get('api/admin/bookManagement/history/{id}', 'BookManagementController@getBorrowingHistory', ['book management']);
    $router->get('api/admin/equipmentManagement/fetch', 'EquipmentManagementController@fetch', ['equipment management']);
    $router->get('api/admin/equipmentManagement/get/{id}', 'EquipmentManagementController@get', ['equipment management']);
    $router->post('api/admin/equipmentManagement/store', 'EquipmentManagementController@store', ['equipment management']);
    $router->post('api/admin/equipmentManagement/update/{id}', 'EquipmentManagementController@update', ['equipment management']);
    $router->post('api/admin/equipmentManagement/toggleActive/{id}', 'EquipmentManagementController@toggleActive', ['equipment management']);
    $router->post('api/admin/qrScanner/scanTicket', 'QRScannerController@scan', ['qr scanner']);
    $router->post('api/admin/qrScanner/borrowTransaction', 'QRScannerController@borrowTransaction', ['qr scanner']);
    $router->get('api/admin/transactionHistory/json', 'TransactionHistoryController@getTransactionsJson', ['transaction history']);
    $router->get('api/admin/borrowingForm/manualBorrow', 'ManualBorrowingController@manualBorrow', ['borrowing form']);
    $router->post('api/admin/borrowingForm/checkUser', 'ManualBorrowingController@checkUser', ['borrowing form']);
    $router->post('api/admin/borrowingForm/create', 'ManualBorrowingController@create', ['borrowing form']);
    $router->get('api/admin/borrowingForm/getEquipments', 'ManualBorrowingController@getEquipments', ['borrowing form']);
    $router->get('api/admin/borrowingForm/getCollaterals', 'ManualBorrowingController@getCollaterals', ['borrowing form']);
    $router->get('api/librarian/returning/getTableData', 'ReturningController@getOverdue', ['returning']);
    $router->get('api/librarian/returning/getRecent', 'ReturningController@getRecentReturnsJson', ['returning']);
    $router->post('api/librarian/returning/checkBook', 'ReturningController@checkBookStatus', ['returning']);
    $router->post('api/admin/returning/markReturned', 'ReturningController@returnBook', ['returning']);
    $router->post('api/admin/returning/extend', 'ReturningController@extendDueDate', ['returning']);
    $router->post('api/admin/returning/sendOverdueEmail', 'ReturningController@sendOverdueEmail', ['returning']);
    $router->get('api/admin/reports/circulated-books', 'ReportController@getCirculatedBooksReport', ['reports']);
    $router->get('api/admin/reports/circulated-equipments', 'ReportController@getCirculatedEquipmentsReport', ['reports']);
    $router->get('api/admin/reports/top-visitors', 'ReportController@getTopVisitors', ['reports']);
    $router->get('api/admin/reports/top-borrowers', 'ReportController@getTopBorrowers', ['reports']);
    $router->get('api/admin/reports/most-borrowed-books', 'ReportController@getMostBorrowedBooks', ['reports']);
    $router->get('api/admin/reports/deleted-books', 'ReportController@getDeletedBooks', ['reports']);
    $router->get('api/admin/reports/library-visits-department', 'ReportController@getLibraryVisitsByDepartment', ['reports']);
    $router->get('api/admin/reports/getGraphData', 'ReportController@getReportGraphData', ['reports']);
    $router->get('api/admin/userManagement/pagination', 'UserManagementController@fetchPaginatedUsers', ['user management']);
    $router->get('api/admin/userManagement/get/{id}', 'UserManagementController@getUserById', ['user management']);
    $router->get('api/admin/userManagement/search', 'UserManagementController@search', ['user management']);
    $router->post('api/admin/userManagement/add', 'UserManagementController@addUser', ['user management']);
    $router->post('api/admin/userManagement/update/{id}', 'UserManagementController@updateUser', ['user management']);
    $router->post('api/admin/userManagement/delete/{id}', 'UserManagementController@deleteUser', ['user management']);
    $router->post('api/admin/userManagement/deleteMultiple', 'UserManagementController@deleteMultipleUsers', ['user management']);
    $router->post('api/admin/userManagement/toggleStatus/{id}', 'UserManagementController@toggleStatus', ['user management']);
    $router->post('api/admin/userManagement/allowEdit/{id}', 'UserManagementController@allowEdit', ['user management']);
    $router->post('api/admin/userManagement/bulkImport', 'UserManagementController@bulkImport', ['user management']);
    $router->get('api/admin/userManagement/getAllCourses', 'DataController@getAllCourses', ['user management']);
    $router->get('api/admin/userManagement/getColleges', 'DataController@getColleges', ['user management']);
    $router->get('api/admin/restoreUser/fetch', 'RestoreUserController@getDeletedUsersJson', ['restore users']);
    $router->post('api/admin/restoreUser/restore', 'RestoreUserController@restore', ['restore users']);
    $router->post('api/admin/restoreUser/delete/{id}', 'RestoreUserController@archive', ['restore users']);
    $router->get('api/admin/dashboard/getData', 'DashboardController@getData', ['reports']);
    $router->post('api/admin/reports/generate-report', 'DomPdfTemplateController@generateLibraryReport', ['reports']);

    $router->get('api/superadmin/userManagement/pagination', 'UserManagementController@fetchPaginatedUsers', ['superadmin']);
    $router->get('api/superadmin/userManagement/get/{id}', 'UserManagementController@getUserById', ['superadmin']);
    $router->get('api/superadmin/userManagement/search', 'UserManagementController@search', ['superadmin']);
    $router->post('api/superadmin/userManagement/add', 'UserManagementController@addUser', ['superadmin']);
    $router->post('api/superadmin/userManagement/update/{id}', 'UserManagementController@updateUser', ['superadmin']);
    $router->post('api/superadmin/userManagement/delete/{id}', 'UserManagementController@deleteUser', ['superadmin']);
    $router->post('api/superadmin/userManagement/deleteMultiple', 'UserManagementController@deleteMultipleUsers');
    $router->post('api/superadmin/userManagement/toggleStatus/{id}', 'UserManagementController@toggleStatus');
    $router->post('api/superadmin/userManagement/allowEdit/{id}', 'UserManagementController@allowEdit', ['superadmin']);
    $router->post('api/superadmin/userManagement/bulkImport', 'UserManagementController@bulkImport');
    $router->get('api/superadmin/userManagement/getAllCourses', 'DataController@getAllCourses', ['superadmin', 'student promotion']);
    $router->get('api/superadmin/userManagement/getColleges', 'DataController@getColleges', ['superadmin']);
    $router->get('api/superadmin/booksmanagement/fetch', 'BookManagementController@fetch', ['superadmin']);
    $router->get('api/superadmin/booksmanagement/get/{id}', 'BookManagementController@getDetails', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/store', 'BookManagementController@store', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/update/{id}', 'BookManagementController@update', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/delete/{id}', 'BookManagementController@destroy', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/deleteMultiple', 'BookManagementController@deleteMultiple', ['superadmin']);
    $router->get('api/superadmin/booksmanagement/history/{id}', 'BookManagementController@getBorrowingHistory', ['superadmin']);
    $router->get('api/superadmin/equipmentManagement/fetch', 'EquipmentManagementController@fetch', ['superadmin']);
    $router->get('api/superadmin/equipmentManagement/get/{id}', 'EquipmentManagementController@get', ['superadmin']);
    $router->post('api/superadmin/equipmentManagement/store', 'EquipmentManagementController@store', ['superadmin']);
    $router->post('api/superadmin/equipmentManagement/update/{id}', 'EquipmentManagementController@update', ['superadmin']);
    $router->post('api/superadmin/equipmentManagement/toggleActive/{id}', 'EquipmentManagementController@toggleActive', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/bulkImport', 'BookManagementController@bulkImport', ['superadmin']);
    $router->post('api/superadmin/qrScanner/scanTicket', 'QRScannerController@scan', ['superadmin']);
    $router->post('api/superadmin/qrScanner/borrowTransaction', 'QRScannerController@borrowTransaction', ['superadmin']);
    $router->get('api/superadmin/qrScanner/transactionHistory', 'QRScannerController@history', ['superadmin']);
    $router->get('api/superadmin/returning/getTableData', 'ReturningController@getOverdue', ['superadmin']);
    $router->get('api/superadmin/returning/getRecent', 'ReturningController@getRecentReturnsJson', ['superadmin']);
    $router->post('api/superadmin/returning/checkBook', 'ReturningController@checkBookStatus', ['superadmin']);
    $router->post('api/superadmin/returning/markReturned', 'ReturningController@returnBook', ['superadmin']);
    $router->post('api/superadmin/returning/extend', 'ReturningController@extendDueDate', ['superadmin']);
    $router->get('api/superadmin/restoreUser/fetch', 'RestoreUserController@getDeletedUsersJson', ['superadmin']);
    $router->post('api/superadmin/restoreUser/restore', 'RestoreUserController@restore', ['superadmin']);
    $router->post('api/superadmin/restoreUser/delete/{id}', 'RestoreUserController@archive', ['superadmin']);
    $router->get('api/superadmin/restoreBooks/fetch', 'RestoreBookController@getDeletedBooksJson', ['superadmin']);
    $router->post('api/superadmin/restoreBooks/restore', 'RestoreBookController@restore', ['superadmin']);
    $router->post('api/superadmin/restoreBooks/archive/{id}', 'RestoreBookController@archive', ['superadmin']);
    $router->get('api/superadmin/backup/export/zip/{table}', 'BackupController@exportBothFormats', ['superadmin']);
    $router->get('api/superadmin/backup/database/full', 'BackupController@initiateBackup', ['superadmin']);
    $router->get('api/superadmin/backup/secure_download/{filename}', 'BackupController@downloadBackup', ['superadmin']);
    $router->get('api/superadmin/backup/logs', 'BackupController@listBackupLogs', ['superadmin']);
    $router->post('api/superadmin/backup/restore/{filename}', 'BackupController@restoreBackup', ['superadmin']);
    $router->post('api/superadmin/backup/delete/{filename}', 'BackupController@deleteBackup', ['superadmin']);
    $router->post('api/superadmin/backup/upload_restore', 'BackupController@uploadAndRestore', ['superadmin']);
    $router->get('api/superadmin/dashboard/stats', 'App\Controllers\DashboardController@getStats', ['superadmin']);
    $router->get('api/superadmin/dashboard/top-visitors', 'App\Controllers\DashboardController@getTopVisitors', ['superadmin']);
    $router->get('api/superadmin/dashboard/weekly-activity', 'App\Controllers\DashboardController@getWeeklyActivity', ['superadmin']);
    $router->get('api/superadmin/dashboard/getData', 'DashboardController@getData', ['superadmin']);
    $router->get('api/superadmin/transactionHistory/json', 'TransactionHistoryController@getTransactionsJson', ['superadmin']);
    $router->get('api/superadmin/borrowingForm/manualBorrow', 'ManualBorrowingController@manualBorrow', ['superadmin']);
    $router->get('api/superadmin/borrowingForm/getEquipments', 'ManualBorrowingController@getEquipments', ['superadmin']);
    $router->get('api/superadmin/borrowingForm/getCollaterals', 'ManualBorrowingController@getCollaterals', ['superadmin']);
    $router->post('api/superadmin/borrowingForm/checkUser', 'ManualBorrowingController@checkUser');
    $router->post('api/superadmin/borrowingForm/create', 'ManualBorrowingController@create');
    $router->get('api/superadmin/reports/circulated-books', 'ReportController@getCirculatedBooksReport', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/circulated-equipments', 'ReportController@getCirculatedEquipmentsReport', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/top-visitors', 'ReportController@getTopVisitors', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/top-borrowers', 'ReportController@getTopBorrowers', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/most-borrowed-books', 'ReportController@getMostBorrowedBooks', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/deleted-books', 'ReportController@getDeletedBooks', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/library-visits-department', 'ReportController@getLibraryVisitsByDepartment', ['superadmin', 'reports']);
    $router->get('api/superadmin/myProfile/get', 'SuperAdminProfileController@getProfile', ['superadmin']);
    $router->post('api/superadmin/myProfile/update', 'SuperAdminProfileController@updateProfile', ['superadmin']);
    $router->post('api/superadmin/returning/sendOverdueEmail', 'ReturningController@sendOverdueEmail', ['superadmin']);
    $router->get('api/superadmin/libraryPolicies/getAll', 'LibraryPolicyController@getAll', ['superadmin']);
    $router->post('api/superadmin/libraryPolicies/update', 'LibraryPolicyController@update', ['superadmin']);

    $router->get('api/superadmin/auditLogs/fetch', 'AuditLogController@fetch', ['superadmin']);
    $router->get('auditLogs', 'AuditLogController@index', ['superadmin']);

    $router->get('api/superadmin/studentPromotion/fetch', 'StudentPromotionController@fetch', ['superadmin', 'student promotion']);
    $router->post('api/superadmin/studentPromotion/promote', 'StudentPromotionController@promote', ['superadmin', 'student promotion']);
    $router->post('api/superadmin/studentPromotion/deactivate', 'StudentPromotionController@deactivate', ['superadmin', 'student promotion']);
    $router->post('api/superadmin/studentPromotion/activate', 'StudentPromotionController@activate', ['superadmin', 'student promotion']);
    $router->get('studentPromotion', 'StudentPromotionController@index', ['superadmin', 'student promotion']);

    $router->post('generate-report', 'DomPdfTemplateController@generateLibraryReport', ['superadmin']);

    $router->get('api/student/attendance/get', 'AttendanceController@getMyAttendance', ['student']);
    $router->get('api/student/cart', 'CartController@index', ['student']);
    $router->get('api/student/cart/add/{id}', 'CartController@add', ['student']);
    $router->post('api/student/cart/remove/{id}', 'CartController@remove', ['student']);
    $router->post('api/student/cart/clear', 'CartController@clearCart', ['student']);
    $router->get('api/student/cart/json', 'CartController@getCartJson', ['student']);
    $router->post('api/student/cart/checkout', 'TicketController@checkout', ['student']);
    $router->get('api/student/qrBorrowingTicket/checkStatus', 'TicketController@checkStatus');
    $router->get('api/student/bookCatalog/availableCount', 'BookCatalogController@getAvailableCount', ['student']);
    $router->get('api/student/bookCatalog/fetch', 'BookCatalogController@fetch', ['student']);
    $router->get('api/student/borrowingHistory/fetch', 'StudentBorrowingHistoryController@fetchHistory', ['student']);
    $router->get('api/student/borrowing-history/stats', 'StudentBorrowingHistoryController@fetchStats', ['student']);
    $router->get('api/student/borrowing-history/pagination', 'StudentBorrowingHistoryController@fetchPaginatedBorrowingHistory', ['student']);
    $router->get('api/student/myprofile/get', 'StudentProfileController@getProfile', ['student']);
    $router->post('api/student/myprofile/update', 'StudentProfileController@updateProfile', ['student']);
    $router->get('api/data/getAllCourses', 'DataController@getAllCourses', ['student']);

    $router->get('api/attendance/logs/ajax', 'AttendanceController@fetchLogsAjax', ['attendance logs', 'superadmin']);
    $router->get('api/superadmin/overdue/getTableData', 'OverdueController@getTableData', ['superadmin', 'overdue tracking']);
    $router->post('api/superadmin/overdue/sendReminder', 'OverdueController@sendReminder', ['superadmin', 'overdue tracking']);

    $router->get('dashboard', 'ViewController@handleDashboard');

    $router->get('{action}', 'ViewController@handleGenericPage');
    $router->get('{action}/{id}', 'ViewController@handleGenericPage');

    return $router;
  }
}
