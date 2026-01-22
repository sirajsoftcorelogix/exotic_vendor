 
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
        sans-serif;
    }

    body {
      background: #f5f5f5;
      color: #333;
    }

    .page {
      max-width: 1400px;
      margin: 0 auto;
      padding: 16px;
    }

    /* Header / store info */
    .store-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #fff;
      padding: 12px 16px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      margin-bottom: 16px;
    }

    .store-header-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .store-logo {
      width: 48px;
      height: 48px;
      border-radius: 4px;
      background: #ddd;
      overflow: hidden;
    }

    .store-title {
      font-size: 18px;
      font-weight: 600;
    }

    .store-meta {
      font-size: 12px;
      color: #777;
      margin-top: 4px;
    }

    .store-header-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary,
    .btn-outline {
      padding: 8px 14px;
      border-radius: 4px;
      font-size: 13px;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      white-space: nowrap;
    }

    .btn-primary {
      background: #f58220;
      color: #fff;
    }

    .btn-outline {
      background: #fff;
      color: #333;
      border: 1px solid #ddd;
    }

    /* Main layout */
    .layout {
      display: grid;
      grid-template-columns: 240px minmax(0, 1fr) 280px;
      gap: 16px;
      margin-top: 16px;
    }

    /* Left filters */
    .sidebar-left {
      background: #fff;
      border-radius: 8px;
      padding: 12px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      height: fit-content;
    }

    .sidebar-left h3 {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .filter-section {
      margin-bottom: 16px;
      border-bottom: 1px solid #eee;
      padding-bottom: 12px;
    }

    .filter-section:last-child {
      border-bottom: none;
      padding-bottom: 0;
      margin-bottom: 0;
    }

    .filter-label {
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .filter-chip-group {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .filter-chip {
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      border: 1px solid #ddd;
      background: #fafafa;
      cursor: pointer;
    }

    .filter-chip.active {
      background: #f58220;
      color: #fff;
      border-color: #f58220;
    }

    .filter-checkbox-list {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-top: 4px;
    }

    .filter-checkbox-item {
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* Products */
    .products-container {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      padding: 12px 12px 16px;
    }

    .products-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .products-header-left h2 {
      font-size: 16px;
      font-weight: 600;
    }

    .products-tabs {
      display: flex;
      gap: 8px;
      margin-top: 6px;
    }

    .products-tab {
      font-size: 12px;
      padding: 4px 10px;
      border-radius: 999px;
      border: 1px solid #ddd;
      background: #fafafa;
      cursor: pointer;
      white-space: nowrap;
    }

    .products-tab.active {
      background: #f58220;
      color: #fff;
      border-color: #f58220;
    }

    .products-header-right {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .select,
    .search-input {
      font-size: 12px;
      padding: 5px 8px;
      border-radius: 4px;
      border: 1px solid #ddd;
      background: #fff;
    }

    .search-input {
      width: 150px;
    }

    .products-grid {
      margin-top: 8px;
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
    }

    .product-card {
      background: #fff;
      border-radius: 6px;
      border: 1px solid #eee;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      cursor: pointer;
      transition: box-shadow 0.15s ease, transform 0.15s ease;
    }

    .product-card:hover {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
      transform: translateY(-1px);
    }

    .product-image {
      width: 100%;
      padding-top: 120%;
      background: #ddd;
      position: relative;
    }

    .product-body {
      padding: 8px;
      display: flex;
      flex-direction: column;
      gap: 4px;
      flex: 1;
    }

    .product-title {
      font-size: 12px;
      font-weight: 600;
      min-height: 32px;
      line-height: 1.3;
    }

    .product-meta {
      font-size: 11px;
      color: #777;
    }

    .product-price-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 4px;
    }

    .product-price {
      font-weight: 600;
      font-size: 13px;
    }

    .product-old-price {
      font-size: 11px;
      color: #999;
      text-decoration: line-through;
      margin-left: 4px;
    }

    .product-cart-btn {
      margin-top: 6px;
      font-size: 12px;
      padding: 4px 8px;
      border-radius: 4px;
      border: 1px solid #f58220;
      color: #f58220;
      background: #fff7ef;
      cursor: pointer;
      width: 100%;
      text-align: center;
    }

    /* Right sidebar */
    .sidebar-right {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .card {
      background: #fff;
      border-radius: 8px;
      padding: 12px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .card-title {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .cart-items {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 8px;
    }

    .cart-item {
      display: flex;
      gap: 8px;
      font-size: 12px;
    }

    .cart-item-thumb {
      width: 40px;
      height: 40px;
      border-radius: 4px;
      background: #ddd;
    }

    .cart-item-info {
      flex: 1;
    }

    .cart-item-title {
      font-size: 12px;
      font-weight: 600;
    }

    .cart-item-meta {
      font-size: 11px;
      color: #777;
    }

    .cart-summary-row {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      margin-top: 4px;
    }

    .cart-summary-row.total {
      font-weight: 600;
      margin-top: 6px;
      border-top: 1px solid #eee;
      padding-top: 6px;
    }

    .card-actions {
      margin-top: 10px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .btn-block {
      width: 100%;
      justify-content: center;
    }

    /* Responsive */
    @media (max-width: 1100px) {
      .layout {
        grid-template-columns: 220px minmax(0, 1fr);
      }

      .sidebar-right {
        grid-column: 1 / -1;
        flex-direction: row;
        align-items: flex-start;
      }

      .sidebar-right .card {
        flex: 1;
      }

      .products-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }

    @media (max-width: 768px) {
      .layout {
        grid-template-columns: minmax(0, 1fr);
      }

      .sidebar-left {
        order: -1;
      }

      .products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .store-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }

      .store-header-right {
        align-self: stretch;
        justify-content: flex-end;
      }
    }

    @media (max-width: 480px) {
      .products-grid {
        grid-template-columns: minmax(0, 1fr);
      }

      .search-input {
        width: 120px;
      }
    }
  </style>
  <div class="page">
    <!-- Store header -->
    <header class="store-header">
      <div class="store-header-left">
        <div class="store-logo"></div>
        <div>
          <div class="store-title">Store_1</div>
          <div class="store-meta">
            Curated collection of antiques &amp; decor · 120 products
          </div>
        </div>
      </div>
      <div class="store-header-right">
        <button class="btn-outline">Share Store</button>
        <button class="btn-primary">Follow</button>
      </div>
    </header>

    <main class="layout">
      <!-- Left filters -->
      <aside class="sidebar-left">
        <div class="filter-section">
          <h3>Filters</h3>
        </div>

        <div class="filter-section">
          <div class="filter-label">Categories</div>
          <div class="filter-chip-group">
            <button class="filter-chip active">All</button>
            <button class="filter-chip">Sculptures</button>
            <button class="filter-chip">Doors</button>
            <button class="filter-chip">Jewellery</button>
            <button class="filter-chip">Textiles</button>
          </div>
        </div>

        <div class="filter-section">
          <div class="filter-label">Availability</div>
          <div class="filter-checkbox-list">
            <label class="filter-checkbox-item">
              <input type="checkbox" checked />
              In stock
            </label>
            <label class="filter-checkbox-item">
              <input type="checkbox" />
              On sale
            </label>
          </div>
        </div>

        <div class="filter-section">
          <div class="filter-label">Price Range</div>
          <div class="filter-checkbox-list">
            <label class="filter-checkbox-item">
              <input type="checkbox" /> $0 – $500
            </label>
            <label class="filter-checkbox-item">
              <input type="checkbox" /> $500 – $1500
            </label>
            <label class="filter-checkbox-item">
              <input type="checkbox" /> $1500+
            </label>
          </div>
        </div>
      </aside>

      <!-- Products center -->
      <section class="products-container">
        <div class="products-header">
          <div class="products-header-left">
            <h2>Products</h2>
            <div class="products-tabs">
              <button class="products-tab active">All Products</button>
              <button class="products-tab">New Arrivals</button>
              <button class="products-tab">Best Sellers</button>
            </div>
          </div>
          <div class="products-header-right">
            <input
              type="text"
              class="search-input"
              placeholder="Search in store"
            />
            <select class="select">
              <option>Sort: Featured</option>
              <option>Price: Low to High</option>
              <option>Price: High to Low</option>
              <option>Newest</option>
            </select>
          </div>
        </div>

        <div class="products-grid">
          <!-- Repeat product cards as needed -->
          <article class="product-card">
            <div class="product-image"></div>
            <div class="product-body">
              <div class="product-title">
                Hand-carved Standing Deity Sculpture
              </div>
              <div class="product-meta">Bronze · 22 in</div>
              <div class="product-price-row">
                <div>
                  <span class="product-price">$1,250</span>
                  <span class="product-old-price">$1,400</span>
                </div>
                <div class="product-meta">In stock</div>
              </div>
              <button class="product-cart-btn">Add to cart</button>
            </div>
          </article>

          <article class="product-card">
            <div class="product-image"></div>
            <div class="product-body">
              <div class="product-title">
                Brass Temple Lamp with Five Flames
              </div>
              <div class="product-meta">Brass · 12 in</div>
              <div class="product-price-row">
                <div>
                  <span class="product-price">$320</span>
                </div>
                <div class="product-meta">In stock</div>
              </div>
              <button class="product-cart-btn">Add to cart</button>
            </div>
          </article>

          <article class="product-card">
            <div class="product-image"></div>
            <div class="product-body">
              <div class="product-title">
                Antique Painted Shrine Panel (Set of 2)
              </div>
              <div class="product-meta">Wood · Vintage</div>
              <div class="product-price-row">
                <div>
                  <span class="product-price">$2,800</span>
                </div>
                <div class="product-meta">Only 1 left</div>
              </div>
              <button class="product-cart-btn">Add to cart</button>
            </div>
          </article>

          <article class="product-card">
            <div class="product-image"></div>
            <div class="product-body">
              <div class="product-title">
                Traditional Handwoven Saree in Ivory
              </div>
              <div class="product-meta">Cotton · Free size</div>
              <div class="product-price-row">
                <div>
                  <span class="product-price">$180</span>
                </div>
                <div class="product-meta">In stock</div>
              </div>
              <button class="product-cart-btn">Add to cart</button>
            </div>
          </article>

          <!-- Add more product-card blocks to match the grid you need -->
        </div>
      </section>

      <!-- Right sidebar: cart & summary -->
      <aside class="sidebar-right">
        <section class="card">
          <div class="card-title">My Basket</div>
          <div class="cart-items">
            <div class="cart-item">
              <div class="cart-item-thumb"></div>
              <div class="cart-item-info">
                <div class="cart-item-title">
                  Hand-carved Standing Deity Sculpture
                </div>
                <div class="cart-item-meta">Qty: 1 · $1,250</div>
              </div>
            </div>
            <div class="cart-item">
              <div class="cart-item-thumb"></div>
              <div class="cart-item-info">
                <div class="cart-item-title">Traditional Handwoven Saree</div>
                <div class="cart-item-meta">Qty: 2 · $180</div>
              </div>
            </div>
          </div>

          <div class="cart-summary-row">
            <span>Subtotal</span>
            <span>$1,610</span>
          </div>
          <div class="cart-summary-row">
            <span>Shipping</span>
            <span>Calculated at checkout</span>
          </div>
          <div class="cart-summary-row total">
            <span>Total</span>
            <span>$1,610</span>
          </div>

          <div class="card-actions">
            <button class="btn-primary btn-block">Checkout</button>
            <button class="btn-outline btn-block">View cart</button>
          </div>
        </section>

        <section class="card">
          <div class="card-title">Store Info</div>
          <p style="font-size: 12px; color: #555; margin-bottom: 6px;">
            Shipping worldwide. All pieces are authenticated and packed with
            care.
          </p>
          <div class="cart-summary-row">
            <span>Contact</span>
            <span style="font-size: 11px;">support@store1.com</span>
          </div>
          <div class="cart-summary-row">
            <span>Location</span>
            <span style="font-size: 11px;">Chennai, India</span>
          </div>
        </section>
      </aside>
    </main>
  </div>
