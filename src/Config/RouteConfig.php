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
    
    // Consolidated Book Catalog Route
    $router->get('bookCatalog', 'BookCatalogController@index', ['faculty', 'staff', 'student']);

    $router->get('api/faculty/cart', 'FacultyCartController@index', ['faculty']);
    $router->get('api/faculty/cart', 'FacultyCartController@index', ['faculty']);
    $router->get('api/faculty/cart/add/{id}', 'FacultyCartController@add', ['faculty']);
    $router->post('api/faculty/cart/remove/{id}', 'FacultyCartController@remove', ['faculty']);
    $router->post('api/faculty/cart/clear', 'FacultyCartController@clearCart', ['faculty']);
    $router->get('api/faculty/cart/json', 'FacultyCartController@getCartJson', ['faculty']);
    $router->post('api/faculty/cart/checkout', 'FacultyTicketController@checkout', ['faculty']);
    $router->get('api/faculty/qrBorrowingTicket', 'FacultyTicketController@show', ['faculty']);
    $router->get('api/faculty/myprofile/get', 'UserProfileController@getProfile', ['faculty']);
    $router->post('api/faculty/myprofile/update', 'UserProfileController@updateProfile', ['faculty']);
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
    $router->get('api/staff/myprofile/get', 'UserProfileController@getProfile', ['staff']);
    $router->post('api/staff/myprofile/update', 'UserProfileController@updateProfile', ['staff']);
    $router->get('api/staff/borrowing-history/pagination', 'StaffBorrowingHistoryController@fetchPaginatedBorrowingHistory', ['staff']);
    $router->get('api/staff/borrowing-history/stats', 'StaffBorrowingHistoryController@fetchStats', ['staff']);


    $router->get('api/librarian/booksmanagement/fetch', 'BookManagementController@fetch', ['book management', 'librarian']);
    $router->get('api/librarian/booksmanagement/get/{id}', 'BookManagementController@getDetails', ['book management', 'librarian']);
    $router->get('api/librarian/booksmanagement/details/{id}', 'BookManagementController@getDetails', ['book management', 'librarian']);
    $router->post('api/librarian/booksmanagement/store', 'BookManagementController@store', ['book management', 'librarian']);
    $router->post('api/librarian/booksmanagement/add', 'BookManagementController@store', ['book management', 'librarian']);
    $router->post('api/librarian/booksmanagement/update/{id}', 'BookManagementController@update', ['book management', 'librarian']);
    $router->post('api/librarian/booksmanagement/delete/{id}', 'BookManagementController@destroy', ['book management', 'librarian']);
    $router->post('api/librarian/booksmanagement/reactivate/{id}', 'BookManagementController@reactivate', ['book management', 'librarian']);
    $router->post('api/librarian/booksmanagement/deleteMultiple', 'BookManagementController@deleteMultiple', ['book management', 'librarian']);
    $router->post('api/librarian/booksmanagement/bulkImport', 'BookManagementController@bulkImport', ['book management', 'librarian']);
    $router->get('api/librarian/booksmanagement/history/{id}', 'BookManagementController@getBorrowingHistory', ['book management', 'librarian']);
    $router->get('api/librarian/equipmentManagement/fetch', 'EquipmentManagementController@getAll', ['equipment management', 'librarian']);
    $router->get('api/librarian/equipmentManagement/get/{id}', 'EquipmentManagementController@get', ['equipment management', 'librarian']);
    $router->post('api/librarian/equipmentManagement/store', 'EquipmentManagementController@store', ['equipment management', 'librarian']);
    $router->post('api/librarian/equipmentManagement/update/{id}', 'EquipmentManagementController@update', ['equipment management', 'librarian']);
    $router->post('api/librarian/equipmentManagement/toggleActive/{id}', 'EquipmentManagementController@toggleActive', ['equipment management', 'librarian']);
    $router->post('api/librarian/equipmentManagement/delete/{id}', 'EquipmentManagementController@destroy', ['equipment management', 'librarian']);
    $router->post('api/librarian/qrScanner/scanTicket', 'QRScannerController@scan', ['qr scanner', 'librarian', 'admin']);
    $router->post('api/librarian/qrScanner/borrowTransaction', 'QRScannerController@borrowTransaction', ['qr scanner', 'librarian', 'admin']);
    $router->get('api/librarian/returning/getTableData', 'ReturningController@getOverdue', ['returning', 'librarian', 'admin']);
    $router->get('api/librarian/returning/getRecent', 'ReturningController@getRecentReturnsJson', ['returning', 'librarian', 'admin']);
    $router->post('api/librarian/returning/checkBook', 'ReturningController@checkBookStatus', ['returning', 'librarian', 'admin']);
    $router->post('api/librarian/returning/markReturned', 'ReturningController@returnBook', ['returning', 'librarian', 'admin']);
    $router->post('api/librarian/returning/extend', 'ReturningController@extendDueDate', ['returning', 'librarian', 'admin']);
    $router->post('api/librarian/returning/sendOverdueEmail', 'ReturningController@sendOverdueEmail', ['returning', 'librarian', 'admin']);
    $router->get('api/librarian/transactionHistory/json', 'TransactionHistoryController@getTransactionsJson', ['transaction history', 'librarian', 'admin']);
    $router->get('api/librarian/reports/circulated-books', 'ReportController@getCirculatedBooksReport', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/circulated-equipments', 'ReportController@getCirculatedEquipmentsReport', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/top-visitors', 'ReportController@getTopVisitors', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/top-borrowers', 'ReportController@getTopBorrowers', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/most-borrowed-books', 'ReportController@getMostBorrowedBooks', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/deleted-books', 'ReportController@getDeletedBooks', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/lost-damaged-books', 'ReportController@getLostDamagedBooksReport', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/library-visits-department', 'ReportController@getLibraryVisitsByDepartment', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/getGraphData', 'ReportController@getReportGraphData', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/getActivityReport', 'ReportController@getActivityReport', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/reports/getGraphData', 'ReportController@getReportGraphData', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/borrowingForm/manualBorrow', 'ManualBorrowingController@manualBorrow', ['borrowing form']);
    $router->post('api/librarian/borrowingForm/checkUser', 'ManualBorrowingController@checkUser', ['borrowing form']);
    $router->post('api/librarian/borrowingForm/create', 'ManualBorrowingController@create', ['borrowing form']);
    $router->get('api/librarian/borrowingForm/getEquipments', 'ManualBorrowingController@getEquipments', ['borrowing form']);
    $router->get('api/librarian/borrowingForm/getCollaterals', 'ManualBorrowingController@getCollaterals', ['borrowing form']);
    $router->post('api/librarian/reports/generate-report', 'DomPdfTemplateController@generateLibraryReport', ['reports', 'librarian', 'admin']);
    $router->get('api/librarian/dashboard/getData', 'DashboardController@getData', ['reports', 'librarian', 'admin']);



     $router->get('api/admin/booksmanagement/fetch', 'BookManagementController@fetch', ['book management', 'librarian']);
    $router->get('api/admin/booksmanagement/get/{id}', 'BookManagementController@getDetails', ['book management', 'librarian']);
    $router->get('api/admin/booksmanagement/details/{id}', 'BookManagementController@getDetails', ['book management', 'librarian']);
    $router->post('api/admin/booksmanagement/store', 'BookManagementController@store', ['book management', 'librarian']);
    $router->post('api/admin/booksmanagement/add', 'BookManagementController@store', ['book management', 'librarian']);
    $router->post('api/admin/booksmanagement/update/{id}', 'BookManagementController@update', ['book management', 'librarian']);
    $router->post('api/admin/booksmanagement/delete/{id}', 'BookManagementController@destroy', ['book management', 'librarian']);
    $router->post('api/admin/booksmanagement/reactivate/{id}', 'BookManagementController@reactivate', ['book management', 'librarian']);
    $router->post('api/admin/booksmanagement/deleteMultiple', 'BookManagementController@deleteMultiple', ['book management', 'librarian']);
    $router->post('api/admin/booksmanagement/bulkImport', 'BookManagementController@bulkImport', ['book management', 'librarian']);
    $router->get('api/admin/booksmanagement/history/{id}', 'BookManagementController@getBorrowingHistory', ['book management', 'librarian']);
    $router->get('api/admin/equipmentManagement/fetch', 'EquipmentManagementController@getAll', ['equipment management', 'librarian']);
    $router->get('api/admin/equipmentManagement/get/{id}', 'EquipmentManagementController@get', ['equipment management', 'librarian']);
    $router->post('api/admin/equipmentManagement/store', 'EquipmentManagementController@store', ['equipment management', 'librarian']);
    $router->post('api/admin/equipmentManagement/update/{id}', 'EquipmentManagementController@update', ['equipment management', 'librarian']);
    $router->post('api/admin/equipmentManagement/toggleActive/{id}', 'EquipmentManagementController@toggleActive', ['equipment management', 'librarian']);
    $router->post('api/admin/equipmentManagement/delete/{id}', 'EquipmentManagementController@destroy', ['equipment management', 'librarian']);
    $router->post('api/admin/qrScanner/scanTicket', 'QRScannerController@scan', ['qr scanner', 'librarian', 'admin']);
    $router->post('api/admin/qrScanner/borrowTransaction', 'QRScannerController@borrowTransaction', ['qr scanner', 'librarian', 'admin']);
    $router->get('api/admin/transactionHistory/json', 'TransactionHistoryController@getTransactionsJson', ['transaction history', 'librarian', 'admin']);
    $router->get('api/admin/borrowingForm/manualBorrow', 'ManualBorrowingController@manualBorrow', ['borrowing form']);
    $router->post('api/admin/borrowingForm/checkUser', 'ManualBorrowingController@checkUser', ['borrowing form']);
    $router->post('api/admin/borrowingForm/create', 'ManualBorrowingController@create', ['borrowing form']);
    $router->get('api/admin/borrowingForm/getEquipments', 'ManualBorrowingController@getEquipments', ['borrowing form']);
    $router->get('api/admin/borrowingForm/getCollaterals', 'ManualBorrowingController@getCollaterals', ['borrowing form']);
    $router->get('api/librarian/returning/getTableData', 'ReturningController@getOverdue', ['returning', 'librarian', 'admin']);
    $router->get('api/librarian/returning/getRecent', 'ReturningController@getRecentReturnsJson', ['returning', 'librarian', 'admin']);
    $router->post('api/librarian/returning/checkBook', 'ReturningController@checkBookStatus', ['returning', 'librarian', 'admin']);
    $router->get('api/admin/returning/getTableData', 'ReturningController@getOverdue', ['returning', 'librarian', 'admin']);
    $router->get('api/admin/returning/getRecent', 'ReturningController@getRecentReturnsJson', ['returning', 'librarian', 'admin']);
    $router->post('api/admin/returning/checkBook', 'ReturningController@checkBookStatus', ['returning', 'librarian', 'admin']);
    $router->post('api/admin/returning/markReturned', 'ReturningController@returnBook', ['returning', 'librarian', 'admin']);
    $router->post('api/admin/returning/extend', 'ReturningController@extendDueDate', ['returning', 'librarian', 'admin']);
    $router->post('api/admin/returning/sendOverdueEmail', 'ReturningController@sendOverdueEmail', ['returning', 'librarian', 'admin']);
    $router->get('api/admin/reports/circulated-books', 'ReportController@getCirculatedBooksReport', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/circulated-equipments', 'ReportController@getCirculatedEquipmentsReport', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/top-visitors', 'ReportController@getTopVisitors', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/top-borrowers', 'ReportController@getTopBorrowers', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/most-borrowed-books', 'ReportController@getMostBorrowedBooks', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/deleted-books', 'ReportController@getDeletedBooks', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/lost-damaged-books', 'ReportController@getLostDamagedBooksReport', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/library-visits-department', 'ReportController@getLibraryVisitsByDepartment', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/getGraphData', 'ReportController@getReportGraphData', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/getActivityReport', 'ReportController@getActivityReport', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/reports/getGraphData', 'ReportController@getReportGraphData', ['reports', 'librarian', 'admin']);
    $router->get('api/admin/myProfile/get', 'UserProfileController@getProfile', ['admin']);
    $router->post('api/admin/myProfile/update', 'UserProfileController@updateProfile', ['admin']);
    $router->get('api/librarian/myProfile/get', 'UserProfileController@getProfile', ['librarian']);
    $router->post('api/librarian/myProfile/update', 'UserProfileController@updateProfile', ['librarian']);
    $router->get('api/campus_admin/myProfile/get', 'UserProfileController@getProfile', ['campus_admin']);
    $router->post('api/campus_admin/myProfile/update', 'UserProfileController@updateProfile', ['campus_admin']);

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
    $router->get('api/admin/dashboard/getData', 'DashboardController@getData', ['reports', 'librarian', 'admin']);
    $router->post('api/admin/reports/generate-report', 'DomPdfTemplateController@generateLibraryReport', ['reports', 'librarian', 'admin']);

    // Bulk Delete Routes
    $router->get('bulkDeleteQueue', 'BulkDeleteController@index', ['bulk delete queue']);
    $router->get('api/bulk-delete/pending', 'BulkDeleteController@fetchPending', ['bulk delete queue']);
    $router->get('api/bulk-delete/get/{id}', 'BulkDeleteController@getDetails', ['bulk delete queue']);
    $router->post('api/bulk-delete/approve', 'BulkDeleteController@approve', ['bulk delete queue']);
    $router->post('api/bulk-delete/reject', 'BulkDeleteController@reject', ['bulk delete queue']);

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
    $router->get('api/superadmin/userManagement/getAllCourses', 'DataController@getAllCourses', ['superadmin']);
    $router->get('api/superadmin/userManagement/getColleges', 'DataController@getColleges', ['superadmin']);
    $router->get('api/campuses/all', 'DataController@getAllCampuses');
    $router->get('api/superadmin/booksmanagement/fetch', 'BookManagementController@fetch', ['superadmin']);
    $router->get('api/superadmin/booksmanagement/get/{id}', 'BookManagementController@getDetails', ['superadmin']);
    $router->get('api/superadmin/booksmanagement/details/{id}', 'BookManagementController@getDetails', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/store', 'BookManagementController@store', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/add', 'BookManagementController@store', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/update/{id}', 'BookManagementController@update', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/delete/{id}', 'BookManagementController@destroy', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/reactivate/{id}', 'BookManagementController@reactivate', ['superadmin']);
    $router->post('api/superadmin/booksmanagement/deleteMultiple', 'BookManagementController@deleteMultiple', ['superadmin']);
    $router->get('api/superadmin/booksmanagement/history/{id}', 'BookManagementController@getBorrowingHistory', ['superadmin']);
    $router->get('api/superadmin/equipmentManagement/fetch', 'EquipmentManagementController@getAll', ['superadmin']);
    $router->get('api/superadmin/equipmentManagement/get/{id}', 'EquipmentManagementController@get', ['superadmin']);
    $router->post('api/superadmin/equipmentManagement/store', 'EquipmentManagementController@store', ['superadmin']);
    $router->post('api/superadmin/equipmentManagement/update/{id}', 'EquipmentManagementController@update', ['superadmin']);
    $router->post('api/superadmin/equipmentManagement/toggleActive/{id}', 'EquipmentManagementController@toggleActive', ['superadmin']);
    $router->post('api/superadmin/equipmentManagement/delete/{id}', 'EquipmentManagementController@destroy', ['superadmin']);
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

    $router->get('api/superadmin/backup/export/zip/{table}', 'BackupController@exportBothFormats', ['superadmin']);
    $router->get('api/superadmin/backup/database/full', 'BackupController@initiateBackup', ['superadmin']);
    $router->get('api/superadmin/backup/secure_download/{filename}', 'BackupController@downloadBackup', ['superadmin']);
    $router->get('api/superadmin/backup/logs', 'BackupController@listBackupLogs', ['superadmin']);
    $router->post('api/superadmin/backup/restore/{filename}', 'BackupController@restoreBackup', ['superadmin']);
    $router->post('api/superadmin/backup/delete/{filename}', 'BackupController@deleteBackup', ['superadmin']);
    $router->post('api/superadmin/backup/upload_restore', 'BackupController@uploadAndRestore', ['superadmin']);
    $router->get('api/superadmin/dashboard/stats', 'DashboardController@getStats', ['superadmin']);
    $router->get('api/superadmin/dashboard/top-visitors', 'DashboardController@getTopVisitors', ['superadmin']);
    $router->get('api/superadmin/dashboard/weekly-activity', 'DashboardController@getWeeklyActivity', ['superadmin']);
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
    $router->get('api/superadmin/reports/lost-damaged-books', 'ReportController@getLostDamagedBooksReport', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/library-visits-department', 'ReportController@getLibraryVisitsByDepartment', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/getActivityReport', 'ReportController@getActivityReport', ['superadmin', 'reports']);
    $router->get('api/superadmin/reports/getReportGraphData', 'ReportController@getReportGraphData', ['superadmin', 'reports']);
    $router->post('api/superadmin/reports/generate-report', 'DomPdfTemplateController@generateLibraryReport', ['superadmin', 'reports']);
    $router->get('api/superadmin/myProfile/get', 'UserProfileController@getProfile', ['superadmin']);
    $router->post('api/superadmin/myProfile/update', 'UserProfileController@updateProfile', ['superadmin']);
    $router->post('api/superadmin/returning/sendOverdueEmail', 'ReturningController@sendOverdueEmail', ['superadmin']);
    $router->get('api/superadmin/libraryPolicies/getAll', 'LibraryPolicyController@getAll', ['superadmin']);
    $router->post('api/superadmin/libraryPolicies/update', 'LibraryPolicyController@update', ['superadmin']);
    $router->get('api/superadmin/campuses/fetch', 'CampusManagementController@fetch', ['superadmin']);
    $router->post('api/superadmin/campuses/store', 'CampusManagementController@store', ['superadmin']);
    $router->post('api/superadmin/campuses/update/{id}', 'CampusManagementController@update', ['superadmin']);
    $router->post('api/superadmin/campuses/toggleStatus/{id}', 'CampusManagementController@toggleStatus', ['superadmin']);
    $router->post('api/superadmin/campuses/delete/{id}', 'CampusManagementController@destroy', ['superadmin']);
    $router->get('campusManagement', 'CampusManagementController@index', ['superadmin']);
    $router->get('api/superadmin/auditLogs/fetch', 'AuditLogController@fetch', ['superadmin']);
    $router->get('auditLogs', 'AuditLogController@index', ['superadmin', 'admin']);

    $router->get('api/superadmin/studentPromotion/fetch', 'StudentPromotionController@fetch', ['superadmin']);
    $router->post('api/superadmin/studentPromotion/promote', 'StudentPromotionController@promote', ['superadmin']);
    $router->post('api/superadmin/studentPromotion/deactivate', 'StudentPromotionController@deactivate', ['superadmin']);
    $router->post('api/superadmin/studentPromotion/activate', 'StudentPromotionController@activate', ['superadmin']);
    $router->get('studentPromotion', 'StudentPromotionController@index', ['superadmin']);

    $router->post('generate-report', 'DomPdfTemplateController@generateLibraryReport', ['superadmin']);

    $router->get('api/campus_admin/userManagement/pagination', 'UserManagementController@fetchPaginatedUsers', ['campus_admin']);
    $router->get('api/campus_admin/userManagement/get/{id}', 'UserManagementController@getUserById', ['campus_admin']);
    $router->get('api/campus_admin/userManagement/search', 'UserManagementController@search', ['campus_admin']);
    $router->post('api/campus_admin/userManagement/add', 'UserManagementController@addUser', ['campus_admin']);
    $router->post('api/campus_admin/userManagement/update/{id}', 'UserManagementController@updateUser', ['campus_admin']);
    $router->post('api/campus_admin/userManagement/delete/{id}', 'UserManagementController@deleteUser', ['campus_admin']);
    $router->post('api/campus_admin/userManagement/deleteMultiple', 'UserManagementController@deleteMultipleUsers');
    $router->post('api/campus_admin/userManagement/toggleStatus/{id}', 'UserManagementController@toggleStatus');
    $router->post('api/campus_admin/userManagement/allowEdit/{id}', 'UserManagementController@allowEdit', ['campus_admin']);
    $router->post('api/campus_admin/userManagement/bulkImport', 'UserManagementController@bulkImport');
    $router->get('api/campus_admin/userManagement/getAllCourses', 'DataController@getAllCourses', ['campus_admin']);
    $router->get('api/campus_admin/userManagement/getColleges', 'DataController@getColleges', ['campus_admin']);
    $router->get('api/campuses/all', 'DataController@getAllCampuses');
    $router->get('api/campus_admin/booksmanagement/fetch', 'BookManagementController@fetch', ['campus_admin']);
    $router->get('api/campus_admin/booksmanagement/get/{id}', 'BookManagementController@getDetails', ['campus_admin']);
    $router->get('api/campus_admin/booksmanagement/details/{id}', 'BookManagementController@getDetails', ['campus_admin']);
    $router->post('api/campus_admin/booksmanagement/store', 'BookManagementController@store', ['campus_admin']);
    $router->post('api/campus_admin/booksmanagement/add', 'BookManagementController@store', ['campus_admin']);
    $router->post('api/campus_admin/booksmanagement/update/{id}', 'BookManagementController@update', ['campus_admin']);
    $router->post('api/campus_admin/booksmanagement/delete/{id}', 'BookManagementController@destroy', ['campus_admin']);
    $router->post('api/campus_admin/booksmanagement/reactivate/{id}', 'BookManagementController@reactivate', ['campus_admin']);
    $router->post('api/campus_admin/booksmanagement/deleteMultiple', 'BookManagementController@deleteMultiple', ['campus_admin']);
    $router->get('api/campus_admin/booksmanagement/history/{id}', 'BookManagementController@getBorrowingHistory', ['campus_admin']);
    $router->get('api/campus_admin/equipmentManagement/fetch', 'EquipmentManagementController@getAll', ['campus_admin']);
    $router->get('api/campus_admin/equipmentManagement/get/{id}', 'EquipmentManagementController@get', ['campus_admin']);
    $router->post('api/campus_admin/equipmentManagement/store', 'EquipmentManagementController@store', ['campus_admin']);
    $router->post('api/campus_admin/equipmentManagement/update/{id}', 'EquipmentManagementController@update', ['campus_admin']);
    $router->post('api/campus_admin/equipmentManagement/toggleActive/{id}', 'EquipmentManagementController@toggleActive', ['campus_admin']);
    $router->post('api/campus_admin/equipmentManagement/delete/{id}', 'EquipmentManagementController@destroy', ['campus_admin']);
    $router->post('api/campus_admin/booksmanagement/bulkImport', 'BookManagementController@bulkImport', ['campus_admin']);
    $router->post('api/campus_admin/qrScanner/scanTicket', 'QRScannerController@scan', ['campus_admin']);
    $router->post('api/campus_admin/qrScanner/borrowTransaction', 'QRScannerController@borrowTransaction', ['campus_admin']);
    $router->get('api/campus_admin/qrScanner/transactionHistory', 'QRScannerController@history', ['campus_admin']);
    $router->get('api/campus_admin/returning/getTableData', 'ReturningController@getOverdue', ['campus_admin']);
    $router->get('api/campus_admin/returning/getRecent', 'ReturningController@getRecentReturnsJson', ['campus_admin']);
    $router->post('api/campus_admin/returning/checkBook', 'ReturningController@checkBookStatus', ['campus_admin']);
    $router->post('api/campus_admin/returning/markReturned', 'ReturningController@returnBook', ['campus_admin']);
    $router->post('api/campus_admin/returning/extend', 'ReturningController@extendDueDate', ['campus_admin']);

    $router->get('api/campus_admin/dashboard/stats', 'DashboardController@getStats', ['campus_admin']);
    $router->get('api/campus_admin/dashboard/top-visitors', 'DashboardController@getTopVisitors', ['campus_admin']);
    $router->get('api/campus_admin/dashboard/weekly-activity', 'DashboardController@getWeeklyActivity', ['campus_admin']);
    $router->get('api/campus_admin/dashboard/getData', 'DashboardController@getData', ['campus_admin']);
    $router->get('api/campus_admin/transactionHistory/json', 'TransactionHistoryController@getTransactionsJson', ['campus_admin']);
    $router->get('api/campus_admin/borrowingForm/manualBorrow', 'ManualBorrowingController@manualBorrow', ['campus_admin']);
    $router->get('api/campus_admin/borrowingForm/getEquipments', 'ManualBorrowingController@getEquipments', ['campus_admin']);
    $router->get('api/campus_admin/borrowingForm/getCollaterals', 'ManualBorrowingController@getCollaterals', ['campus_admin']);
    $router->post('api/campus_admin/borrowingForm/checkUser', 'ManualBorrowingController@checkUser');
    $router->post('api/campus_admin/borrowingForm/create', 'ManualBorrowingController@create');
    $router->get('api/campus_admin/reports/circulated-books', 'ReportController@getCirculatedBooksReport', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/circulated-equipments', 'ReportController@getCirculatedEquipmentsReport', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/top-visitors', 'ReportController@getTopVisitors', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/top-borrowers', 'ReportController@getTopBorrowers', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/most-borrowed-books', 'ReportController@getMostBorrowedBooks', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/deleted-books', 'ReportController@getDeletedBooks', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/lost-damaged-books', 'ReportController@getLostDamagedBooksReport', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/library-visits-department', 'ReportController@getLibraryVisitsByDepartment', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/getActivityReport', 'ReportController@getActivityReport', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/reports/getReportGraphData', 'ReportController@getReportGraphData', ['campus_admin', 'reports']);
    $router->post('api/campus_admin/reports/generate-report', 'DomPdfTemplateController@generateLibraryReport', ['campus_admin', 'reports']);
    $router->get('api/campus_admin/myProfile/get', 'UserProfileController@getProfile', ['campus_admin']);
    $router->post('api/campus_admin/myProfile/update', 'UserProfileController@updateProfile', ['campus_admin']);
    $router->post('api/campus_admin/returning/sendOverdueEmail', 'ReturningController@sendOverdueEmail', ['campus_admin']);
    $router->get('api/campus_admin/libraryPolicies/getAll', 'LibraryPolicyController@getAll', ['campus_admin']);

    $router->get('api/campus_admin/studentPromotion/fetch', 'StudentPromotionController@fetch', ['campus_admin']);
    $router->post('api/campus_admin/studentPromotion/promote', 'StudentPromotionController@promote', ['campus_admin']);
    $router->post('api/campus_admin/studentPromotion/deactivate', 'StudentPromotionController@deactivate', ['campus_admin']);
    $router->post('api/campus_admin/studentPromotion/activate', 'StudentPromotionController@activate', ['campus_admin']);
    $router->get('studentPromotion', 'StudentPromotionController@index', ['campus_admin']);

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
    $router->get('api/student/myprofile/get', 'UserProfileController@getProfile', ['student']);
    $router->post('api/student/myprofile/update', 'UserProfileController@updateProfile', ['student']);

    $router->get('api/data/getAllCourses', 'DataController@getAllCourses', ['student']);

    $router->get('api/campus_admin/overdue/getTableData', 'OverdueController@getTableData', ['campus_admin']);
    $router->post('api/campus_admin/overdue/sendReminder', 'OverdueController@sendReminder', ['campus_admin']);

    $router->get('api/superadmin/overdue/getTableData', 'OverdueController@getTableData', ['superadmin', 'overdue tracking', 'campus_admin']);
    $router->post('api/superadmin/overdue/sendReminder', 'OverdueController@sendReminder', ['superadmin', 'overdue tracking', 'campus_admin']);
    $router->get('api/attendance/logs/ajax', 'AttendanceController@fetchLogsAjax', ['attendance logs', 'superadmin', 'campus_admin']);

    $router->get('dashboard', 'ViewController@handleDashboard');

    $router->get('{action}', 'ViewController@handleGenericPage');
    $router->get('{action}/{id}', 'ViewController@handleGenericPage');

    return $router;
  }
}
