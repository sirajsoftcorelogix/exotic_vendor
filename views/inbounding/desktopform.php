
    <style>
        /* --- Global Reset and Container --- */
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; padding: 20px; font-size: 13px; }
        .container { background-color: #fff; padding: 20px; border-radius: 4px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        h2 { font-size: 18px; margin-top: 0; margin-bottom: 20px; font-weight: 500; color: #333; }
        h3 { font-size: 15px; margin-top: 20px; margin-bottom: 10px; font-weight: bold; color: #333; }
        hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
        
        /* --- Form Elements and Utility --- */
        .form-group { position: relative; margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 12px; }
        .form-group input[type="text"], 
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc; /* Standard light border */
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 13px;
        }
        .prefilled-text { color: red; font-size: 11px; margin-top: 2px; }
        
        /* --- 1. Header/Linking Section --- */
        .item-header {
            display: flex;
            align-items: flex-start;
            gap: 40px;
            margin-bottom: 25px;
            border: 1px solid #ccc; /* Visible light border around the whole header block */
            padding: 15px;
            border-radius: 4px;
        }
        .item-image-box img { width: 80px; height: 80px; border: 1px solid #eee; padding: 5px; }
        .item-linking-details { flex-grow: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 30px; }
        
        .header-field-pair { display: flex; align-items: center; gap: 10px; }
        .header-field-pair label { font-weight: normal; margin: 0; }
        .header-field-pair input[type="text"] { width: 150px; }
        .header-field-pair button { 
            padding: 6px 15px; 
            background-color: #f0ad4e; 
            color: white; 
            border: none; 
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }
        
        /* Receipt Info */
        .receipt-info {
            display: flex;
            gap: 30px;
            margin: 15px 0 25px 0;
            font-size: 12px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee; /* Bottom border to separate from Identification */
        }
        .receipt-info-item p { margin: 0; padding-top: 5px; }
        .receipt-info-item span { font-weight: bold; color: #333; }

        /* --- 2. Item Identification --- */
        .identification-grid-rows { display: flex; gap: 20px; }
        .id-column { flex: 1; min-width: 0; }
        .id-column-narrow { flex: 0 0 30%; } 
        
        /* Checkbox Groups - Border and scroll */
        .checkbox-container {
            border: 1px solid #ccc; /* Border around the checkbox group */
            padding: 5px 10px;
            height: 100px;
            overflow-y: auto;
            border-radius: 4px;
            background-color: #fff;
        }
        .checkbox-container label {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-bottom: 5px;
            cursor: pointer;
            font-size: 13px;
        }

        /* --- 4. Related Items --- */
        .related-items-section { 
            text-align: center; 
            border: 1px solid #ccc; /* Border around related items section */
            padding: 15px;
            border-radius: 4px;
        }
        .related-items-section button { padding: 8px 15px; background-color: #f0ad4e; color: white; border: none; border-radius: 4px; margin-bottom: 15px; cursor: pointer; font-size: 13px; }
        .related-items-list { display: flex; justify-content: space-around; gap: 10px; }
        .related-item-box { border: 1px solid #eee; padding: 10px; text-align: center; width: 18%; border-radius: 4px; font-size: 12px; }
        .related-item-box img { max-width: 100%; height: auto; border-radius: 4px; }
        
        /* --- 5. Upload & Vendor --- */
        .upload-vendor-row { display: flex; gap: 20px; margin-top: 20px; align-items: flex-start; }
        .upload-box {
            border: 1px solid #ccc; /* Subtle border around the upload wrapper */
            padding: 10px;
            text-align: center;
            width: 30%;
            border-radius: 4px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100px;
        }
        .upload-content { 
            border: 2px dashed #ccc; /* Dashed border inside the upload box */
            padding: 15px; 
            width: 80%; 
            margin-top: 5px;
            border-radius: 4px;
        }
        .vendor-group { flex: 1; position: relative; }
        .vendor-group button { position: absolute; right: 0; top: 35px; background: #fff; border: 1px solid #ccc; color: #333; padding: 5px 10px; border-radius: 4px; font-size: 12px; cursor: pointer; }

        /* --- 6. Pricing & Dimensions --- */
        .price-dimension-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 10px; }
        .price-dimension-group { padding: 5px 0; }
        .price-label { font-weight: bold; margin-bottom: 5px; text-align: center; }
        
        /* Pricing Input style - Combined input/unit border */
        .price-input-group { 
            display: flex; 
            align-items: center; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            background-color: #fff;
        }
        .price-input-group input { 
            flex: 1; 
            min-width: 30px; 
            padding: 7px; 
            border: none; 
            background: none; 
            text-align: left; 
        }
        .price-input-group .unit { 
            margin-left: auto; 
            padding-right: 5px;
            font-weight: normal; 
            color: #555; 
            font-size: 12px; 
        }

        /* Dimension Input style */
        .dimension-input-group { display: flex; align-items: center; }
        .dimension-input-group input { flex: 1; padding: 7px; border: 1px solid #ccc; border-radius: 4px; text-align: left; }
        .dimension-input-group span { margin-left: 5px; color: #555; font-size: 12px; }

        /* --- 7. Stock --- */
        .stock-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 15px; }

        /* --- 8. Save Button --- */
        .save-button-container { text-align: right; margin-top: 30px; }
        .save-button-container button {
            padding: 10px 25px;
            background-color: #f0ad4e;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>


<div class="container">
    <h2>Inbound - Product details update</h2>

    <div class="item-header">
        <div class="item-image-box">
            <img src="placeholder-goddess-idol.jpg" alt="Goddess Idol Image">
        </div>
        
        <div class="item-linking-details">
            <div class="form-group">
                <label>Item Linking</label>
                <div class="header-field-pair">
                    <label>Variant:</label>
                    <label><input type="radio" name="variant" value="Yes" checked> Yes</label>
                </div>
            </div>
            <div class="form-group">
                <label for="dateAdded">Date Added</label>
                <input type="text" id="dateAdded" value="1 Nov 2025" disabled>
            </div>
            
            <div class="form-group">
                <label for="parentItemCode">Parent Item Code: (Show only others Variant to Yes)</label>
                <div class="header-field-pair" style="gap: 5px;">
                    <input type="text" id="parentItemCode" value="" style="flex: 1;">
                    <button>Explore</button>
                </div>
            </div>
            <div class="form-group">
                <label for="stockUpdatedOn">Stock Updated On</label>
                <input type="text" id="stockUpdatedOn" value="1 Nov 2025" disabled>
            </div>
        </div>
    </div>
    
    <div class="receipt-info">
        <div class="receipt-info-item">
            <label>Receipt</label>
            <p>Gate Entry Date & Time: <span>01 Nov 2025 11:15 AM</span></p>
        </div>
        <div class="receipt-info-item">
            <label>Received by</label>
            <p>Uttam Kumar</p>
        </div>
        <div class="receipt-info-item">
            <label>Updated by</label>
            <p>Shivam Kumar</p>
        </div>
    </div>

    <h3>Item Identification</h3>
    <div class="identification-grid-rows">
        <div class="id-column">
            <div class="form-group">
                <label for="material">Material</label>
                <select id="material">
                    <option selected>change prefilled value if item exists</option>
                </select>
                <p class="prefilled-text">Prefilled</p>
            </div>

            <div class="form-group">
                <label for="group">Group</label>
                <select id="group">
                    <option selected>change prefilled value if item exists</option>
                </select>
                <p class="prefilled-text">Prefilled</p>
            </div>
            
             <div class="form-group">
                <label for="status">Status</label>
                <select id="status">
                    <option selected>change prefilled value if item exists</option>
                </select>
                <p class="prefilled-text">Prefilled</p>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category">
                    <option selected>Hindu god and goddesses</option>
                </select>
            </div>
        </div>

        <div class="id-column-narrow">
            <div class="form-group">
                <label>Sub Category</label>
                <div class="checkbox-container">
                    <label><input type="checkbox"> change prefilled value if item exists</label>
                    <label><input type="checkbox"> Mordem Art</label>
                    <label><input type="checkbox"> Ganesha</label>
                    <label><input type="checkbox"> Wooden Carving</label>
                    <label><input type="checkbox" checked> Goddess</label>
                </div>
            </div>
        </div>
        
        <div class="id-column-narrow">
            <div class="form-group">
                <label>SubSubCategory</label>
                <div class="checkbox-container">
                    <label><input type="checkbox"> Mordem Art</label>
                    <label><input type="checkbox"> Ganesha</label>
                    <label><input type="checkbox"> Wooden Carving</label>
                    <label><input type="checkbox" checked> Goddess</label>
                </div>
            </div>
        </div>
    </div>
    
    <hr>
    
    <h3>Item Identification</h3>
    <div style="display: flex; gap: 20px;">
        <div class="form-group" style="flex: 1;">
            <label for="title">Title</label>
            <input type="text" id="title">
        </div>
    </div>
    <div style="display: flex; gap: 20px;">
        <div class="form-group" style="flex: 1;">
            <label for="keywords">Keywords</label>
            <input type="text" id="keywords">
        </div>
    </div>

    <hr>

    <h3>Related items</h3>
    <div class="related-items-section">
        <button>Load Realted Items</button>
        <div class="related-items-list">
            <div class="related-item-box">
                <img src="placeholder-item1.jpg" alt="Related Item PHC439">
                <p>PHC439</p>
            </div>
            <div class="related-item-box">
                <img src="placeholder-item2.jpg" alt="Related Item PHC229">
                <p>PHC229</p>
            </div>
            <div class="related-item-box">
                <img src="placeholder-item3.jpg" alt="Related Item DDN829">
                <p>DDN829</p>
            </div>
            <div class="related-item-box">
                <img src="placeholder-item4.jpg" alt="Related Item DDN836">
                <p>DDN836</p>
            </div>
            <div class="related-item-box">
                <img src="placeholder-item5.jpg" alt="Related Item DDP774">
                <p>DDP774</p>
            </div>
        </div>
    </div>

    <hr>

    <div class="upload-vendor-row">
        <div class="upload-box">
            <div style="position: absolute; top: 10px; left: 10px; color: red;">&#x2716;</div>
            <img src="placeholder-file-icon.png" alt="File Icon" style="width: 30px; height: 30px; margin: 0 auto 5px auto;">
            <p style="font-weight: bold; margin-bottom: 2px;">Upload Invoice Image</p>
            <p>Drag or paste a file here or choose an option below</p>
        </div>
        <div class="vendor-group">
            <label for="vendor">Vendor:</label>
            <input type="text" id="vendor" value="Swethamber Arts & Crafts">
            <p class="prefilled-text">Prefilled if item exists</p>
            <button>(Auto Search)</button>
        </div>
    </div>

    <hr>
    
    <h3>Pricing</h3>
    <div class="price-dimension-row">
        <div class="price-dimension-group">
            <div class="price-label">INR Price</div>
            <div class="price-input-group">
                <input type="text" value="110"><span class="unit">INR</span>
            </div>
        </div>
        <div class="price-dimension-group">
            <div class="price-label">Amazon Price</div>
            <div class="price-input-group">
                <input type="text" value="110"><span class="unit">INR</span>
            </div>
        </div>
        <div class="price-dimension-group">
            <div class="price-label">USD Price</div>
            <div class="price-input-group">
                <input type="text" value="110"><span class="unit">USD</span>
            </div>
        </div>
        <div class="price-dimension-group">
            <div class="price-label">GST</div>
            <div class="price-input-group">
                <input type="text" value="110"><span class="unit">INR</span>
            </div>
        </div>
        <div></div>
    </div>

    <hr>

    <h3>Dimensions: <span class="prefilled-text">Prefilled if item exists</span></h3>
    <div class="price-dimension-row">
        <div class="price-dimension-group">
            <div class="price-label">Height</div>
            <div class="dimension-input-group">
                <input type="text" value="110"><span>inc</span>
            </div>
        </div>
        <div class="price-dimension-group">
            <div class="price-label">Width</div>
            <div class="dimension-input-group">
                <input type="text" value="110"><span>inc</span>
            </div>
        </div>
        <div class="price-dimension-group">
            <div class="price-label">Depth</div>
            <div class="dimension-input-group">
                <input type="text" value="110"><span>inc</span>
            </div>
        </div>
        <div class="price-dimension-group">
            <div class="price-label">Weight</div>
            <div class="dimension-input-group">
                <input type="text" value="110"><span>inc</span>
            </div>
        </div>
        <div class="price-dimension-group">
            <div class="price-label">Size</div>
            <div class="dimension-input-group">
                <input type="text" value="XL">
            </div>
        </div>
    </div>

    <hr>

    <h3>Stock</h3>
    <div class="stock-grid">
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <div class="dimension-input-group">
                <input type="text" id="quantity" value="10"><span>NOS</span>
            </div>
            <p class="prefilled-text">Prefilled</p>
        </div>
        <div class="form-group">
            <label for="permanentlyAvailable">Permanently Available</label>
            <select id="permanentlyAvailable">
                <option selected>No</option>
            </select>
        </div>
        <div class="form-group">
            <label for="warehouse">Warehouse</label>
            <select id="warehouse">
                <option selected>Wasimpur</option>
            </select>
        </div>
        <div class="form-group">
            <label for="storeLocation">Store Location</label>
            <input type="text" id="storeLocation" value="HP134">
        </div>

        <div class="form-group">
            <label for="localStock">Local Stock</label>
            <div class="dimension-input-group">
                <input type="text" id="localStock" value="10"><span>NOS</span>
            </div>
            <p class="prefilled-text">Prefilled</p>
        </div>
        <div class="form-group">
            <label for="inStockLeadTime">In Stock Lead Time</label>
            <select id="inStockLeadTime">
                <option selected>--</option>
            </select>
        </div>
        <div class="form-group">
            <label for="usStock">US Stock</label>
            <select id="usStock">
                <option selected>No</option>
            </select>
        </div>
        <div class="form-group">
            <label for="localStock2">Local Stock</label>
            <input type="text" id="localStock2" value="HP134">
        </div>
    </div>

    <div class="save-button-container">
        <button>Save and Generate Item Code</button>
    </div>

</div>
