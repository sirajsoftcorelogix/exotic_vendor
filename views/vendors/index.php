<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mt-5">
        <!-- Header Section with Filters and Actions -->
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow">
           <div class="flex flex-wrap items-center gap-4">
            <span class="text-gray-600 font-medium">Vendors</span>
           </div>
           <div class="flex flex-wrap items-center gap-4">
                <a href="<?php echo base_url('?page=vendors&action=add') ?>" >Create Vendor</a>
            </div>
            <div id="open-popup-btn" class="text-center font-medium"> Add Vendor</div>
        </div>
        
    </div>
    <!-- Vendor Table Container -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Name</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company Person</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor Email</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor Phone</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">City</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zip Code</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GST Number</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PAN Number</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Type</th>
                        <th class="px-6 py-3 bg-gray-50"> Action</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($vendors)): ?>
                        <?php foreach ($vendors as $index => $vendor): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?= $index + 1 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['contact_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['company_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['vendor_email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['vendor_phone']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['address']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['city']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['state']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['postal_code']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['country']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['gst_number']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['pan_number']) ?></td> 
                                <td class="px-6 py-4 whitespace-nowrap"><?= $vendor['business_type'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="?page=vendors&action=update&id=<?= $vendor['id'] ?>" class="text-blue-600 hover:text-blue-900 " title="Edit"><i class="fa fa-edit"></i></a>
                                    <!-- <form action="?page=vendors&action=delete" method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?= $vendor['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete"><i class="fa fa-trash"></i></button>
                                    </form> -->
                                    <button class="btn btn-sm btn-danger mt-0" onclick="deleteData(<?= $vendor['id'] ?>)" title="Delete"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="px-6 py-4 text-center text-gray-500">No vendors found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function deleteData(id) {
            const msgDiv = document.getElementById('addUserMsg');
            if (confirm('Are you sure you want to delete this vendor?')) {
                fetch('?page=vendors&action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                })  
                .then(response => response.json())  
                .then(data => {
                    if (data.success) { 
                        //alert(data.message);
                        msgDiv.textContent = data.message;
                        msgDiv.style.color = data.success ? 'green' : 'red';
                        document.querySelector(`tr[data-id="${id}"]`).remove();
                    } else {
                        //alert(data.message); 
                        msgDiv.textContent = data.message;
                        msgDiv.style.color = data.success ? 'green' : 'red';   
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the vendor.');
                });
            }
        }
    </script>
    <!-- Pagination Controls -->
    <div class="flex items-center justify-between bg-white rounded-xl shadow-md p-4">
        <div>
            <p class="text-sm text-gray-600">Showing <span class="font-medium"><?= count($vendors) ?></span> of <span class="font-medium"><?= count($vendors) ?></span> vendors</p>
        </div>
        <div class="flex items-center space-x-2">
            <?php if ($total_pages > 1): ?>
            <?php if ($page > 1): ?>
            <button id="prev-page" class="p-2 rounded-full hover:bg-gray-100">
                <a class="page-link" href="?page=vendors&action=list&page_no=<?= $page-1 ?>&limit=<?= $limit ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
            </button>
            <?php endif; ?>
            <!-- Page Numbers -->
            <?php /* for ($i = 1; $i <= $total_pages; $i++): ?>
               <a href="?page=vendors&action=list&page_no=<?= $i ?>&limit=<?= $limit ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"><?= $i ?></a>
            <?php endfor; */ ?>
            <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page ?></span>
            <?php if ($page < $total_pages): ?>
            <button id="next-page" class="p-2 rounded-full hover:bg-gray-100">
                <a class="page-link" href="?page=vendors&action=list&page_no=<?= $page+1 ?>&limit=<?= $limit ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            </button>
            <span class="text-sm text-gray-600">of <?= $total_pages ?></span>
            <?php endif; ?>
            <?php endif; ?>
            <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                    onchange="location.href='?page=vendors&action=list&page_no=1&limit=' + this.value;">
                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                        <?= $opt ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>
<!-- Right Side Popup -->
<div id="right-popup" class="popup fixed top-0 right-0 h-full bg-white shadow-2xl transform translate-x-full z-50 overflow-y-auto" style="width: 45%; min-width: 600px;">
    <div class="p-8">        

        

    </div>
    <div class="flex justify-end items-center gap-4 pt-6 border-t">
        <button type="button" id="cancel-btn" class="action-btn cancel-btn">Cancel</button>
        <!-- <button type="submit" class="action-btn save-btn">Save</button> -->
    </div>
</div>        
<script>
    const openPopupBtn = document.getElementById('open-popup-btn');
    const rightPopup = document.getElementById('right-popup');
    const cancelBtn = document.getElementById('cancel-btn');    

    // --- Popup Logic ---
    openPopupBtn.addEventListener('click', () => {
        rightPopup.classList.remove('translate-x-full');
        addFormCall();
    });

    cancelBtn.addEventListener('click', () => {
        rightPopup.classList.add('translate-x-full');
    });

    // Form submission
    // document.getElementById('right-form').addEventListener('submit', (e) => {
    //     e.preventDefault();
    //     // Add your save logic here
    //     console.log('right form submitted');
    //     rightPopup.classList.add('translate-x-full');
    // });
    function addFormCall(){
        fetch('?page=vendors&action=add')
        .then(response => response.text())
        .then(html => {
            document.querySelector('#right-popup .p-8').innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching form:', error);
        });
    }
</script>