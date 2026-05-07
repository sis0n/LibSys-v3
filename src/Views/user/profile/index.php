<script>
    window.BASE_URL = "<?= BASE_URL ?>";
    window.STORAGE_URL = "<?= STORAGE_URL ?>";
    window.USER_ROLE = "<?= $_SESSION['role'] ?? 'guest' ?>";
</script>

<div class="min-h-screen">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
            My Profile
        </h2>
        <p class="text-gray-700">Manage your account information and view your activity</p>
    </div>

    <form id="profileForm" class="bg-white px-6 py-4 max-w-max mx-auto rounded-lg shadow-md border border-gray-200">
        <h3 class="text-lg font-semibold mb-2">
            <i class="ph ph-user-circle"></i>
            Profile Information
        </h3>
        
        <div class="flex flex-col items-center mb-6">
            <div class="w-32 h-32 rounded-full bg-emerald-500 flex items-center justify-center text-white text-4xl font-bold mb-3 overflow-hidden relative group">
                <i id="profileIcon" class="ph ph-user-circle text-5xl"></i>
                <img id="profilePreview" src="" alt="Profile" class="hidden w-full h-full object-cover">
                
                <!-- Hover Overlay for Upload -->
                <label for="uploadProfile" id="uploadOverlay" class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer hidden">
                    <i class="ph ph-camera text-white text-3xl"></i>
                </label>
            </div>

            <div class="flex items-center gap-2">
                <p id="profileName" class="text-xl font-semibold text-gray-800">Loading...</p>
                <span id="verificationBadge" class="hidden items-center gap-1 bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    <i class="ph-fill ph-seal-check text-base"></i>
                    Verified
                </span>
            </div>
            
            <span id="profileIdentifier" class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full flex items-center gap-1 mt-1 mb-3">
                Loading...
            </span>

            <label for="uploadProfile" id="uploadLabel" class="cursor-pointer text-sm text-green-600 font-medium hover:underline hidden">
                Upload Image
            </label>
            <input id="uploadProfile" type="file" accept="image/*" class="hidden">

            <p class="text-xs text-gray-500 mt-1 inline-flex items-center gap-1">
                <i class="ph ph-info text-gray-400 text-sm relative"></i>
                Only images below 1 MB are accepted.
            </p>
        </div>

        <!-- Cropper Modal -->
        <div id="cropModal" class="fixed inset-0 bg-black/60 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg p-4 max-w-lg w-full shadow-lg">
                <h3 class="text-lg font-semibold mb-3 text-gray-700">Adjust your profile picture</h3>
                <div class="w-full h-80 flex items-center justify-center border border-gray-200 mb-4 overflow-hidden rounded-lg bg-gray-50">
                    <img id="cropImage" src="" alt="To crop" class="max-h-full select-none">
                </div>
                <div class="flex justify-center gap-3 mb-4">
                    <button type="button" id="zoomIn" class="px-3 py-1 border rounded text-sm hover:bg-gray-100">Zoom In</button>
                    <button type="button" id="zoomOut" class="px-3 py-1 border rounded text-sm hover:bg-gray-100">Zoom Out</button>
                    <button type="button" id="resetCrop" class="px-3 py-1 border rounded text-sm hover:bg-gray-100">Reset</button>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelCrop" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-100">Cancel</button>
                    <button type="button" id="saveCrop" class="px-4 py-2 text-sm rounded-md bg-orange-600 text-white hover:bg-orange-700">Save</button>
                </div>
            </div>
        </div>

        <!-- BASIC INFO SECTION -->
        <section class="mt-6 border-t border-gray-200 pt-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">
                    <i class="ph ph-identification-card"></i>
                    Basic Information
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

                <!-- Campus -->
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="campusName">Campus</label>
                    <input type="text" id="campusName" name="campus_name"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>

                <!-- Email -->
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="email">Email</label>
                    <input type="email" id="email" name="email"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>

                <!-- Contact -->
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="contact">Contact</label>
                    <input type="tel" id="contact" name="contact" maxlength="11"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>
            </div>
        </section>

        <!-- STUDENT SPECIFIC SECTION -->
        <section id="studentDetailsSection" class="mt-6 border-t border-gray-200 pt-6 mb-6 hidden">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="ph ph-student"></i>
                Student Details
            </h3>

            <div class="flex flex-wrap ml-9 items-start gap-x-10 gap-y-6">
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="studentNumber">Student Number</label>
                    <input type="text" id="studentNumber" name="student_number"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>

                <div class="min-w-[300px] flex-grow">
                    <label class="text-sm text-gray-500" for="course">Course</label>
                    <select id="course" name="course_id"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-3 py-2 w-full"
                        disabled>
                        <option value="" disabled selected>Loading courses...</option>
                    </select>
                </div>

                <div class="w-24">
                    <label class="text-sm text-gray-500" for="yearLevel">Year</label>
                    <input type="text" maxlength="1" id="yearLevel" name="year_level"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 text-center w-full"
                        disabled>
                </div>

                <div class="w-24">
                    <label class="text-sm text-gray-500" for="section">Section</label>
                    <input type="text" maxlength="1" id="section" name="section"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 text-center w-full uppercase"
                        disabled>
                </div>
            </div>

            <!-- Registration Documents -->
            <div class="mt-6 border-t border-gray-100 pt-6">
                <h4 class="text-md font-semibold text-gray-600 mb-3">Registration Documents</h4>
                <div class="bg-orange-50/50 border border-orange-200/80 rounded-lg p-3 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <i class="ph ph-file-text text-2xl text-blue-500"></i>
                        <div>
                            <p class="font-medium text-gray-800 text-sm">Registration Form</p>
                            <p class="text-[11px] text-gray-500">Please upload your current semester's registration form (PDF).</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="file" id="regFormUpload" name="reg_form" accept="application/pdf" class="sr-only" disabled />
                        <label for="regFormUpload" id="uploadBtn"
                            class="cursor-pointer px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-100 transition hidden text-center">Upload</label>
                        <a id="viewRegForm" href="#" target="_blank"
                            class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-100 transition hidden">View</a>
                        <button type="button" id="removeRegForm"
                            class="px-3 py-1.5 text-xs rounded-md border border-red-300 bg-red-50 text-red-600 hover:bg-red-100 transition hidden">Remove</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- STAFF/FACULTY SPECIFIC SECTION -->
        <section id="employmentDetailsSection" class="mt-6 border-t border-gray-200 pt-6 mb-6 hidden">
             <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="ph ph-briefcase"></i>
                Employment Details
            </h3>
            <div class="flex flex-wrap ml-9 items-start gap-x-10 gap-y-6">
                <div class="min-w-[200px]">
                    <label class="text-sm text-gray-500" for="employeeId">Employee ID</label>
                    <input type="text" id="employeeId" name="employee_id"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>
                <div class="min-w-[200px]" id="deptWrapper">
                    <label class="text-sm text-gray-500" id="deptLabel" for="collegeId">Department</label>
                    <select id="collegeId" name="college_id"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                        <option value="" disabled selected>Select Department</option>
                    </select>
                </div>
                <div id="positionWrapper" class="min-w-[200px] hidden">
                    <label class="text-sm text-gray-500" for="position">Position</label>
                    <input type="text" id="position" name="position"
                        class="font-medium text-gray-800 bg-gray-50 border-gray-200 border rounded-md px-2 py-1 w-full"
                        disabled>
                </div>
            </div>
        </section>

        <div id="formActions" class="flex justify-end gap-3 mt-8 pt-4 border-t hidden">
            <button type="button" id="cancelProfileBtn"
                class="px-6 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-100 transition">Cancel</button>
            <button type="submit" id="saveProfileBtn"
                class="px-6 py-2 text-sm rounded-md bg-green-600 text-white hover:bg-green-700 transition">Save Changes</button>
        </div>

        <div id="profileLockedInfo" class="text-center text-gray-500 text-sm mt-6 p-4 bg-gray-100 rounded-md hidden">
            <i class="ph ph-lock text-lg mr-1"></i>
            Your profile has been updated once and is now locked.
        </div>
    </form>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<script src="<?= BASE_URL ?>/js/user/myProfile.js" defer></script>
