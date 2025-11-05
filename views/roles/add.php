<div class="bg-white p-4 md:p-8">
    <h1>Edit Role</h1>
    <form action="<?php echo base_url('?page=roles&action=add_role'); ?>" id="add_role" method="POST">
        <div class="flex flex-col md:flex-row justify-between mb-8">
            <div class="h-full w-full overflow-y-auto">
                <div class="pt-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Role Name <span class="text-red-500">*</span></label>
                        <input type="text" class="form-input w-full mt-1" required name="addRName" id="addRName" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Description</label>
                        <textarea class="w-full min-h-[90px] p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" name="addRDescription" id="addRDescription"></textarea>
                    </div>
                </div>
                <div class="pt-4"><label class="text-sm font-medium text-gray-700">Permissions</label></div>
                <div class="pt-4"><?php echo $modules_str;?></div>
                <div class="pt-4 grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                        <select class="form-input w-full mt-1" required name="addStatus" id="addStatus">
                            <option value="1">Active</option>
                            <option value="0">Inactive </option>
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
