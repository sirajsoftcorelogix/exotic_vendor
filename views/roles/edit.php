<div class="bg-white p-4 md:p-8">
    <h1>Edit Role</h1>
    <form action="<?php echo base_url('?page=roles&action=edit_role'); ?>" id="edit_role" method="POST">
        <input type="hidden" id="role_id" name="role_id" value="<?php echo $roles["id"]; ?>">
        <div class="flex flex-col md:flex-row justify-between mb-8">
            <div class="h-full w-full overflow-y-auto">
                <div class="pt-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Role Name <span class="text-red-500">*</span></label>
                        <input type="text" class="form-input w-full mt-1 required" required name="editRName" id="editRName" value="<?php echo $roles["role_name"];?>" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Description</label>
                        <textarea class="w-full min-h-[90px] p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" name="editRDescription" id="editRDescription"><?php echo $roles["role_description"];?></textarea>
                    </div>
                </div>
                <div class="pt-4"><label class="text-sm font-medium text-gray-700">Permissions</label></div>
                <div class="pt-4" id="editModuleStr"><?php echo $modules_str;?></div>
                <div class="pt-4 grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                        <select class="form-input w-full mt-1" required name="editStatus" id="editStatus">
                            <option value="1" <?php if($roles["is_active"] == 1) { echo "selected"; }?>>Active</option>
                            <option value="0" <?php if($roles["is_active"] == 0) { echo "selected"; }?>>Inactive </option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-center items-center gap-4 pt-6 border-t">
                    <button type="submit" class="bg-blue-500 text-white font-semibold py-2 px-4 rounded-md">Save Changes</button>
                    <button type="button" onclick="window.location.href='<?php echo base_url('?page=roles&action=list'); ?>'" class="bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-md">Back</button>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    const requiredFields = document.querySelectorAll('.required');
    // Optional: Auto-trim leading spaces on input
    requiredFields.forEach(field => {
        field.addEventListener('input', function() {
            if (this.value.charAt(0) === ' ') {
                this.value = this.value.trimStart(); // Remove leading spaces
            }
        });
    });
</script>