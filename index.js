// ===== Mobile hamburger menu =====
function toggleMenu() {
  const nav = document.getElementById("navMenu");
  if (nav) {
    nav.classList.toggle("show");
  }
}

// Grab the elements
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');

if (searchInput && searchResults) {
  // Listen for typing
  searchInput.addEventListener('keyup', async (e) => {
    const query = e.target.value.trim();

    // If they typed less than 2 letters, hide the box
    if (query.length < 2) {
      searchResults.style.display = 'none';
      searchResults.innerHTML = '';
      return;
    }

    try {
      // Fetch data from our new backend file
      const response = await fetch(`ajax_search.php?q=${encodeURIComponent(query)}`);
      const data = await response.json();

      if (data.length > 0) {
        // Build the HTML for the dropdown
        let html = '';
        data.forEach(item => {
          html += `
            <a href="product.php?id=${item.product_id}" class="search-item">
              <img src="uploads/${item.image}" alt="${item.product_name}">
              <div class="search-item-details">
                <span class="search-item-name">${item.product_name}</span>
                <span class="search-item-cat">${item.category}</span>
                <span class="search-item-price">R${parseFloat(item.price).toFixed(2)}</span>
              </div>
            </a>
          `;
        });
        
        // Show the results
        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
      } else {
        // No results found
        searchResults.innerHTML = '<div style="padding: 15px 20px; color: #777;">No products found...</div>';
        searchResults.style.display = 'block';
      }
    } catch (error) {
      console.error("Search failed:", error);
    }
  });

  // Hide the dropdown if the user clicks anywhere else on the page
  document.addEventListener('click', (e) => {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.style.display = 'none';
    }
  });
}