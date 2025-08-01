<!-- 
  Plans:
    - Add in functionality to add food
    - Add functionality to add fridges
    - Add functionality to when opening the form to create a new recipe, grab all available food from databse asynchronously and put it in cache
    - Add functionality to add food when adding a recipe
    - Add functionality to functions of showing pages/ forms for a variable that relates to which form to be passed.
 -->

<!DOCTYPE html>
<html lang="en" translate="no">
<head>
	<?php include 'head.php';?>
</head>
<body>
	<?php include 'body-header.php'; ?>
	<div class="d-flex">
		<!-- Popup Form -->
		<div id="recipeForm" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup" style="display: none; z-index: 1050; max-width: 400px;">
      <form>
        <h4 id="form-label-text" class="mb-3">Add New Recipe</h4>

        <!-- Page 1 -->
        <div id="formPage1">
          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="name" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Name Of Dish</label>
            <input type="text" class="form-control" id="name" name="name" required style="width: 100%;">
          </div>

          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="serving" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Servings</label>
            <input type="text" class="form-control" id="serving" name="serving" required style="width: 100%;">
          </div>

          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="closeForm('recipeForm')">Close</button>
            <button type="button" class="btn btn-primary" onclick="showPage('recipeForm', 2)">Next</button>
          </div>
        </div>

        <!-- Page 2 -->
        <div id="formPage2" style="display: none;">
          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="ingredients" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Ingredients</label>
            <input type="text" class="form-control" id="ingredients" name="ingredients" required style="width: 100%;">
          </div>

          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="prepTime" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Prep Time</label>
            <input type="text" class="form-control" id="prepTime" name="prepTime" required style="width: 100%;">
          </div>

          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="showPage('recipeForm', 1)">Back</button>
            <button type="submit" class="btn btn-success">Submit</button>
          </div>
        </div>
      </form>
    </div>
    <div id="foodForm" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup" style="display: none; z-index: 1050; max-width: 400px;">
      <form>
        <h4 id="form-label-text" class="mb-3">Add New Food</h4>

        <!-- Page 1 -->
        <div id="formPage1">
          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="hannaford-link" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Hannaford Link</label>
            <input type="text" class="form-control" id="hannaford-link" name="hannaford-link" required style="width: 100%;">
          </div>

          <!-- <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="serving" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Servings</label>
            <input type="text" class="form-control" id="serving" name="serving" required style="width: 100%;">
          </div> -->

          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="closeForm('foodForm')">Close</button>
            <button type="button" class="btn btn-primary" onclick="showPage('foodForm', 2)">Next</button>
          </div>
        </div>

        <!-- Page 2 -->
        <div id="formPage2" style="display: none;">
          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="ingredients" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Ingredients</label>
            <input type="text" class="form-control" id="ingredients" name="ingredients" required style="width: 100%;">
          </div>

          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="prepTime" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Prep Time</label>
            <input type="text" class="form-control" id="prepTime" name="prepTime" required style="width: 100%;">
          </div>

          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="showPage('foodForm', 1)">Back</button>
            <button type="submit" class="btn btn-success">Submit</button>
          </div>
        </div>
      </form>
    </div>


		<div id="sidebar" class="bg-light border-end vh-100 d-flex flex-column p-3 collapsed">
      <button id="toggleBtn" class="btn btn-outline-secondary mb-4">></button>
      
      <button class="btn btn-info mb-2" onclick="openForm('recipeForm')">
        <i class="bi bi-house"></i>
        <span class="btn-label">Make New Recipe</span>
      </button>

      <button class="btn btn-secondary mb-2" onclick="openForm('foodForm')">
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


    function openForm(formID) {
      const popup = document.getElementById(formID);
      if (popup) popup.style.display = "block";
    }

    function closeForm(formID) {
      const popup = document.getElementById(formID);
      if (popup) popup.style.display = "none";
      showPage(formID, 1); // Reset to Page 1
    }

    function showPage(formID, pageNumber) {
      const formPage1 = document.querySelector(`#${formID} #formPage1`);
      const formPage2 = document.querySelector(`#${formID} #formPage2`);
      const labelText = document.querySelector(`#${formID} #form-label-text`);

      if (!formPage1 || !formPage2 || !labelText) return;

      switch (pageNumber) {
        case 1:
          formPage1.style.display = "block";
          formPage2.style.display = "none";
          labelText.innerHTML = "Add New Recipe";
          break;
        case 2:
          formPage1.style.display = "none";
          formPage2.style.display = "block";
          labelText.innerHTML = "Add Steps for Preparation";
          break;
      }
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

    const linkInput = document.getElementById("hannaford-link");
    let debounceTimer;

    linkInput.addEventListener("input", (event) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const value = event.target.value.trim();

        // Check if input is empty
        if (!value) {
          console.log("Input cleared. Skipping fetch.");
          return;
        }

        // Check if value is a valid URL
        try {
          new URL(value);
        } catch {
          console.log("Invalid URL format. Skipping fetch.");
          return;
        }

        fetch('/proxy?url=' + encodeURIComponent(event.target.value))
          .then(res => res.json())
          .then(data => renderNutrition(data));
      }, 800);
    });

    function renderNutrition(nutritionArray) {
      if (!Array.isArray(nutritionArray)) {
        console.warn("Unexpected response format");
        return;
      }

      console.log("Nutrition Facts:", nutritionArray);
      // TODO: Insert into DOM as needed
    }

  </script>

  <!-- Bootstrap 5 JS bundle (optional) -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>