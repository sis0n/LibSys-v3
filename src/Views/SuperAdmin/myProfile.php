<div class="min-h-screen">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
            My Profile
        </h2>
        <p class="text-gray-700">Manage your account information and view your activity</p>
    </div>

    <!-- Ginawang FORM ang buong section -->
    <form id="profileForm" class="bg-white px-6 py-4 max-w-max mx-auto rounded-lg shadow-md border border-gray-200">
        <h3 class="text-lg font-semibold mb-2">
            <i class="ph ph-user-circle"></i>
            Profile Information
        </h3>
        <div class="flex flex-col items-center mb-6">
            <p id="profileName" class="text-xl font-semibold text-gray-800">Loading...</p>
            <span id="profileStudentId"
                class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full flex items-center gap-1 mt-1 mb-3">
                Loading...
            </span>
        </div>

        <section class="mt-6 border-t border-gray-200 pt-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">
                    <i class="ph ph-identification-card"></i>
                    Super Admin Information
                </h3>
                <button type="button" id="editProfileBtn"
                    class="px-4 py-1.5 text-sm rounded-md border border-orange-600 bg-orange-50 text-orange-700 hover:bg-orange-100 transition hidden">
                    <i class="ph ph-pencil-simple text-sm mr-1"></i>
                    Edit Profile
                </button>
            </div>

            <div class="flex flex-wrap ml-9 items-start gap-x-10 gap-y-6">
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="last_name"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="firstName">First Name</label>
                    <input type="text" id="firstName" name="first_name"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="middleName">Middle Name</label>
                    <input type="text" id="middleName" name="middle_name"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="suffix">Suffix</label>
                    <input type="text" id="suffix" name="suffix"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="gender">Gender</label>
                    <select id="gender" name="gender" class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full" disabled>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="LGBTQIA+">LGBTQIA+</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="genderOther" name="gender_other" placeholder="Please specify" class="hidden font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full mt-2" disabled>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-x-6 gap-y-6 ml-9">
                    <div class="sm:col-span-2">
                        <label class="text-sm text-gray-500" for="email">Email</label>
                        <input type="email" id="email" name="email"
                            class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                            disabled>
                    </div>
                </div>
            </div>
        </section>

        <div id="formActions" class="flex justify-end gap-3 mt-8 pt-4 border-t hidden">
            <button type="button" id="cancelProfileBtn"
                class="px-6 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-100 transition">Cancel</button>
            <button type="submit" id="saveProfileBtn"
                class="px-6 py-2 text-sm rounded-md bg-green-600 text-white hover:bg-green-700 transition">Save
                Changes</button>
        </div>
    </form>
</div>

<script>
    window.BASE_URL = "<?= BASE_URL ?>";
</script>

<script src="<?= BASE_URL ?>/js/superadmin/myProfile.js" defer></script>