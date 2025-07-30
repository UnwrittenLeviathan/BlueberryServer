<!DOCTYPE html>
<html lang="en" translate="no">
<head>
	<?php include 'head.php';?>
</head>
<body>
	<?php include 'body-header.php'; ?>
	<div class="d-flex">
		<!-- Popup Form -->
		<div id="popupForm" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup" style="display: none; z-index: 1050; max-width: 400px;">
		  <form>
		    <h4 class="mb-3">Contact Us</h4>
		    
		    <div class="mb-3">
		      <label for="name" class="form-label">Name</label>
		      <input type="text" class="form-control" id="name" name="name" required>
		    </div>

		    <div class="mb-3">
		      <label for="email" class="form-label">Email</label>
		      <input type="email" class="form-control" id="email" name="email" required>
		    </div>

		    <div class="d-flex justify-content-between">
		      <button type="submit" class="btn btn-success">Submit</button>
		      <button type="button" class="btn btn-secondary" onclick="closeForm()">Close</button>
		    </div>
		  </form>
		</div>

		<div id="sidebar" class="bg-light border-end vh-100 d-flex flex-column p-3 collapsed">
      <button id="toggleBtn" class="btn btn-outline-secondary mb-4">></button>
      
      <button class="btn btn-primary mb-2" onclick="openForm()">
        <i class="bi bi-house"></i>
        <span class="btn-label">Make New Recipe</span>
      </button>

      <button class="btn btn-secondary mb-2">
        <i class="bi bi-person"></i>
        <span class="btn-label">Add New Food Item</span>
        <!-- Add functionality to webscrape from hannaford website -->
      </button>

      <button class="btn btn-success mb-2">
        <i class="bi bi-gear"></i>
        <span class="btn-label">Add Fridge</span>
        <!-- Update to have existing fridge be the option -->
      </button>
    </div>

    <!-- Page Content -->
    <div class="flex-grow-1 p-4 my-4" style="width: 90%; margin: 0 auto;">
      <div class="row" id="itemsRow">
	      <!-- JS will inject columns + buttons here -->
	    </div>
    </div>
	</div>

  <script>
    // Your list of items
    const items = [];

    const MAX_PER_COLUMN = 10;
    const MAX_COLUMNS    = 3;

    // Calculate needed columns (1–3)
    const neededCols = Math.min(
      MAX_COLUMNS,
      Math.ceil(items.length / MAX_PER_COLUMN)
    );

    // Determine Bootstrap column width for md+ breakpoints
    const mdWidth = 12 / neededCols;
    const colClass = `col-12 col-md-${mdWidth}`;

    const containerRow = document.getElementById('itemsRow');

    // Build each column
    for (let c = 0; c < neededCols; c++) {
      const colDiv = document.createElement('div');
      colDiv.className = `${colClass} mb-3`;

      // Use Bootstrap's grid utility to stack buttons with spacing
      const stackDiv = document.createElement('div');
      stackDiv.className = 'd-grid gap-2';

      const start = c * MAX_PER_COLUMN;
      const end   = start + MAX_PER_COLUMN;
      const chunk = items.slice(start, end);

      chunk.forEach(text => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-info';
        btn.textContent = text;
        stackDiv.appendChild(btn);
      });

      colDiv.appendChild(stackDiv);
      containerRow.appendChild(colDiv);
    }

    document.addEventListener("keydown", function(event) {
		  if (event.key === "Escape") {
		    const openForms = document.querySelectorAll(".form-container, .popup, .modal");

		    openForms.forEach(form => {
		      // You can add additional logic here if needed
		      form.style.display = "none";
		    });
		  }
		});


    function openForm() {
	    document.getElementById("popupForm").style.display = "block";
	  }

	  function closeForm() {
	    document.getElementById("popupForm").style.display = "none";
	  }
	  const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      if(toggleBtn.innerHTML.includes('☰')) {
      	toggleBtn.innerHTML = '>'
      } else {
      	toggleBtn.innerHTML = '☰'
      }
    });

  </script>

  <!-- Bootstrap 5 JS bundle (optional) -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>