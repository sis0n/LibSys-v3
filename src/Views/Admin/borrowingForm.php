    <main class="min-h-screen">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">Borrowing Form</h2>
        <div class="text-gray-700">Manually process for borrowing library items.</div>
    </div>

    <form id="main-borrow-form">
        <!-- User Credentials Section -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mt-6">
            <!-- Header -->
            <div class="flex items-start gap-4 mb-8">
                <i class="ph ph-user-list text-3xl text-amber-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">User Credentials</h3>
                    <p class="text-sm text-gray-600">Enter the borrower's information</p>
                </div>
            </div>

            <div class="space-y-4">


                 <!-- Other Details -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label for="input_user_id" class="block text-sm font-medium text-gray-700 mb-1">User ID <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="input_user_id" name="input_user_id"
                            class="w-full px-4 py-2 rounded-lg border border-amber-300 bg-white focus:ring-2 focus:ring-amber-500 outline-none transition text-sm">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="hidden" id="role" name="role" value="Select Role">
                            <button type="button" id="roleDropdownBtn"
                                class="w-full flex items-center justify-between px-4 py-2 rounded-lg border border-amber-300 bg-white text-left focus:ring-2 focus:ring-amber-500 outline-none transition text-sm">
                                <span id="roleDropdownValue">Select Role</span>
                                <i class="ph ph-caret-down text-gray-500"></i>
                            </button>
                            <div id="roleDropdownMenu"
                                class="absolute mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden z-20">
                                <ul class="py-1">
                                    <li><button type="button"
                                            class="role-item w-full text-left px-4 py-2 text-sm hover:bg-amber-50"
                                            data-value="Student">Student</button></li>
                                    <li><button type="button"
                                            class="role-item w-full text-left px-4 py-2 text-sm hover:bg-amber-50"
                                            data-value="Staff">Staff</button></li>
                                    <li><button type="button"
                                            class="role-item w-full text-left px-4 py-2 text-sm hover:bg-amber-50"
                                            data-value="Faculty">Faculty</button></li>
                                    <li><button type="button"
                                            class="role-item w-full text-left px-4 py-2 text-sm hover:bg-amber-50"
                                            data-value="Guest">Guest</button></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="collateral_id" class="block text-sm font-medium text-gray-700 mb-1">Collateral ID
                            <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" id="collateral_id" name="collateral_id"
                                placeholder="Library ID, School ID, Valid ID ..." autocomplete="off"
                                class="w-full px-4 py-2 rounded-lg border border-amber-300 bg-white focus:ring-2 focus:ring-amber-500 outline-none transition text-sm pr-10">
                            <button type="button" id="collateral_id_dropdown_arrow"
                                class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <i class="ph ph-caret-down"></i>
                            </button>
                            <div id="collateral_id_suggestions"
                                class="absolute mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden z-10">
                                <ul class="py-1" id="collateral_id_suggestions_list">
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" id="contact" name="contact"
                            class="w-full px-4 py-2 rounded-lg border border-amber-300 bg-white focus:ring-2 focus:ring-amber-500 outline-none transition text-sm">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="text" id="email" name="email"
                            class="w-full px-4 py-2 rounded-lg border border-amber-300 bg-white focus:ring-2 focus:ring-amber-500 outline-none transition text-sm">
                    </div>
                    
                    
                </div>
                <!-- Name -->
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" placeholder="Juan" name="first_name"
                                class="w-full px-4 py-2 rounded-lg border border-amber-300 bg-white focus:ring-2 focus:ring-amber-500 outline-none transition text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                            <input type="text" placeholder="Dela" name="middle_name"
                                class="w-full px-4 py-2 rounded-lg border border-amber-300 bg-white focus:ring-2 focus:ring-amber-500 outline-none transition text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" placeholder="Cruz" name="last_name"
                                class="w-full px-4 py-2 rounded-lg border border-amber-300 bg-white focus:ring-2 focus:ring-amber-500 outline-none transition text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Suffix</label>
                            <input type="text" name="suffix" placeholder="(Jr., Sr.)"
                                class="w-full px-4 py-2 rounded-lg border border-amber-300 bg-white focus:ring-2 focus:ring-amber-500 outline-none transition text-sm">
                        </div>
                    </div>
                </div>

               
            </div>
        </div>


        <!-- Item Information Section -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 mt-6">
            <div class="flex items-start gap-4 mb-8">
                <i id="item_icon" class="ph ph-desktop text-3xl text-emerald-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Item Information</h3>
                    <p class="text-sm text-gray-600">Enter the item details</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Item Type Dropdown -->
                <div>
                    <label for="item_type" class="block text-sm font-medium text-gray-700 mb-1">Item Type <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="hidden" id="item_type" name="item_type" value="Equipment">
                        <button type="button" id="itemTypeDropdownBtn"
                            class="w-full flex items-center justify-between px-4 py-2 rounded-lg border border-emerald-300 bg-white text-left focus:ring-2 focus:ring-emerald-500 outline-none transition text-sm">
                            <span id="itemTypeDropdownValue">Equipment</span>
                            <i class="ph ph-caret-down text-gray-500"></i>
                        </button>
                        <div id="itemTypeDropdownMenu"
                            class="absolute mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden z-10">
                            <ul class="py-1">
                                <li><button type="button"
                                        class="item-type-item w-full text-left px-4 py-2 text-sm hover:bg-emerald-50"
                                        data-value="Equipment">Equipment</button></li>
                                <li><button type="button"
                                        class="item-type-item w-full text-left px-4 py-2 text-sm hover:bg-emerald-50"
                                        data-value="Book">Book</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-1" id="accession_number_wrapper" style="display: none;">
                    <label for="accession_number" class="block text-sm font-medium text-gray-700 mb-1">Accession Number
                        <span class="text-red-500">*</span></label>
                    <input type="text" id="accession_number" name="accession_number"
                        placeholder="Enter accession number"
                        class="w-full px-4 py-2 rounded-lg border border-emerald-300 bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition text-sm">
                </div>

                <div class="md:col-span-1" id="book_title_wrapper" style="display: none;">
                    <label for="book_title" class="block text-sm font-medium text-gray-700 mb-1">Book Title <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="book_title" name="book_title" placeholder="Enter book title"
                        class="w-full px-4 py-2 rounded-lg border border-emerald-300 bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition text-sm">
                </div>

                <div class="md:col-span-1" id="item_name_wrapper">
                    <label id="item_name_label" for="item_name"
                        class="block text-sm font-medium text-gray-700 mb-1">Equipment Name <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="item_name" name="equipment_name" placeholder="Enter equipment name"
                            autocomplete="off" autocorect="off" spellcheck="false"
                            class="w-full px-4 py-2 rounded-lg border border-emerald-300 bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition text-sm pr-10">
                        <button type="button" id="item_name_dropdown_arrow"
                            class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <i class="ph ph-caret-down"></i>
                        </button>
                        <div id="item_name_suggestions"
                            class="absolute mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden z-10">
                            <ul class="py-1" id="item_name_suggestions_list">
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-4 mt-6">
            <button type="button" id="check-btn"
                class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                Check User
            </button>
            <button type="button"
                class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium"
                id="clear-btn">
                Clear
            </button>
            <button type="submit"
                class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                Submit
            </button>
        </div>
    </form>

    <script src="<?= BASE_URL ?>/js/admin/borrowingForm.js" defer></script>
</main>