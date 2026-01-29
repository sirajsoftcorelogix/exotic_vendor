<div class="max-w-7xl mx-auto py-8">
    
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Category Markup Module</h1>
            <p class="mt-1 text-sm text-gray-500">Manage global price markups for each product category.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <?php $formAction = base_url('?page=category&action=updateMarkup'); ?>
        <form action="<?php echo $formAction; ?>" method="POST">
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Category Name
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-48">
                                Markup Percentage
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($data['category'])): ?>
                            <?php foreach ($data['category'] as $row): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150 group">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        #<?php echo $row['id']; ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900">
                                                <?php echo $row['display_name']; ?>
                                            </span>
                                            <span class="text-xs text-gray-400 font-mono mt-0.5">
                                                (<?php echo $row['name']; ?>)
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
									    <div class="relative rounded-md shadow-sm w-32">
									        <input type="number" 
									               step="0.01" 
									               min="0" 
									               name="markup[<?php echo $row['category']; ?>]" 
									               value="<?php echo isset($row['markup_perct']) ? $row['markup_perct'] : 0; ?>" 
									               class="block w-full rounded-md border-0 py-2 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-white"
									               placeholder="0.00"
									        >
									        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
									            <span class="text-gray-500 sm:text-sm font-medium">%</span>
									        </div>
									    </div>
									</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No categories found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end border-t border-gray-200">
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                    Save Changes
                </button>
            </div>

        </form>
    </div>
</div>