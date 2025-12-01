<div class="bg-white p-4 md:p-8">
    <h1>Edit Inbounding</h1>
    <form action="<?php echo base_url('?page=inbounding&action=edit_inbounding'); ?>" id="edit_inbounding" method="POST">
        <input type="hidden" id="id" name="id" value="<?php echo $inbounding["id"]; ?>">
        <div class="flex flex-col md:flex-row justify-between mb-8">
            <div class="h-full w-full overflow-y-auto">
                <div class="pt-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Product Name <span class="text-red-500">*</span></label>
                        <input type="text" class="form-input w-full mt-1 required" required name="name" id="name" value="<?php echo $inbounding["name"];?>" />
                    </div>
                </div>
                <div class="flex justify-center items-center gap-4 pt-6 border-t">
                    <button type="submit" class="bg-blue-500 text-white font-semibold py-2 px-4 rounded-md">Save Changes</button>
                    <button type="button" onclick="window.location.href='<?php echo base_url('?page=inbounding&action=list'); ?>'" class="bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-md">Back</button>
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