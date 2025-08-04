<!DOCTYPE html>
<html lang="en" translate="no">
<head>
	<?php include 'head.php';?>
</head>
<body>
	<?php include 'body-header.php'; ?>
  <div id="autoAlert" class="alert alert-info alert-dismissible fade position-fixed top-0 start-50 translate-middle-x mt-3" role="alert" style="z-index: 1050; display: none;">
    <strong>Info:</strong>
    <div id="autoAlertInfo">
      Your action was successful!
    </div>
  </div>

	<div class="d-flex">
		<!-- Popup Form -->
		<div id="recipeForm" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup" style="display: none; z-index: 10; max-width: 400px; resize: both; overflow: auto;">
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
    <div id="foodForm" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup" style="display: none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 80%;">
      <form id="addFood">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 id="form-label-text" class="mb-0">Add New Food</h4>
          <button type="button" class="btn btn-danger" id="removeFoodFields" style="display: none;">
            Remove Fields
          </button>
        </div>

        <div id="foodDiv">
          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="foodItem" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Link or Food Name:</label>
            <input type="text" class="form-control" id="foodItem" name="foodItem" required style="width: 100%;">
          </div>
          <hr id="foodRule" class="hr-blurry my-3">

          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="closeForm('foodForm')">Close</button>
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
      </button>

      <button class="btn btn-success mb-2">
        <i class="bi bi-gear"></i>
        <span class="btn-label">Add Food to Fridge</span>
      </button>
    </div>

    <!-- Page Content -->
    <div class="container-fluid h-100 d-flex justify-content-start align-items-start">
      
    </div>

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
    const removeButton = document.getElementById('removeFoodFields');
    const alertEl = document.getElementById("autoAlert");
    const infoBox = document.getElementById('autoAlertInfo');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      if(toggleBtn.innerHTML.includes('☰')) {
        toggleBtn.innerHTML = '>'
      } else {
        toggleBtn.innerHTML = '☰'
      }
    });

    const linkInput = document.getElementById("foodItem");
    let debounceTimer;

    removeButton.addEventListener('click', () => {
      document.querySelectorAll('.auto-scraped').forEach(element => {
        element.remove();
      });
      removeButton.style.display = "none";
      linkInput.value = "";
    });

    linkInput.addEventListener("input", (event) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const value = event.target.value.trim();

        // Check if input is empty
        if (!value) {
          console.log("Input cleared. Skipping fetch.");
          document.querySelectorAll('.auto-scraped').forEach(element => {
            element.remove();
          });
          return;
        }

        // Check if value is a valid URL
        try {
          new URL(value);
        } catch {
          console.log("Invalid URL format. Skipping fetch.");
          return;
        }

        document.querySelectorAll('.auto-scraped').forEach(element => {
          element.remove();
        });

        fetch('/proxy?url=' + encodeURIComponent(event.target.value))
          .then(res => res.json())
          .then(data => {
              data.push({
                nutrient: 'Html Link',
                amount: value,
              });
            renderNutrition(data);
          });
      }, 800);
    });

    function renderNutrition(nutritionArray) {
      if (!Array.isArray(nutritionArray)) {
        console.warn("Unexpected response format");
        return;
      }

      const parentDiv = document.getElementById('foodForm');
      const formContainer = parentDiv.querySelector('#foodDiv');
      const referenceChild = formContainer.querySelector('#foodRule');

      linkInput.value = nutritionArray[0]['product'];
      removeButton.style.display = 'inline-block';

      nutritionArray.filter(item => item.nutrient).reverse().forEach(item => {
        let numeric = '';
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-3 d-flex align-items-center flex-nowrap auto-scraped';
        wrapper.style.maxWidth = '100%';

        const label = document.createElement('label');
        const fieldId = item.nutrient.toLowerCase().replace(/\s+/g, '-').replace(/-/g, "_");
        if(fieldId.includes("html")) {
          numeric = item.amount;
        } else {
          numeric = (item.amount.match(/[\d.]+/) || [''])[0];
        }
        label.setAttribute('for', fieldId);
        label.className = 'form-label me-2 mb-0 text-nowrap auto-scraped';
        label.style.width = '140px';
        label.textContent = item.nutrient.replace(/-/g, "_");

        const input = document.createElement('input');
        input.type = 'text';
        input.id = fieldId;
        input.name = fieldId;
        input.required = false;
        input.className = 'form-control auto-scraped';
        input.style.width = '100%';
        input.value = numeric;

        wrapper.append(label, input);

        if (referenceChild && referenceChild.nextSibling) {
          formContainer.insertBefore(wrapper, referenceChild.nextSibling);
        } else {
          // If there's no next sibling, just append at the end
          formContainer.appendChild(wrapper);
        }

        if (item.nutrient === "Serving Size") {
          const fullText = item.amount.replace(/[^a-zA-Z\s]/g, '').replace(/-/g, "_").trim();
          const extraWrapper = document.createElement('div');
          extraWrapper.className = 'mb-3 d-flex align-items-center flex-nowrap auto-scraped';
          extraWrapper.style.maxWidth = '100%';

          const extraLabel = document.createElement('label');
          const extraId = 'serving_unit';
          extraLabel.setAttribute('for', extraId);
          extraLabel.className = 'form-label me-2 mb-0 text-nowrap auto-scraped';
          extraLabel.style.width = '140px';
          extraLabel.textContent = "Serving Unit";

          const extraInput = document.createElement('input');
          extraInput.type = 'text';
          extraInput.id = extraId;
          extraInput.name = extraId;
          extraInput.className = 'form-control auto-scraped';
          extraInput.style.width = '100%';
          extraInput.value = fullText;

          extraWrapper.append(extraLabel, extraInput);
          formContainer.insertBefore(extraWrapper, wrapper.nextSibling);
        }
      });
    }

    document.getElementById("addFood").addEventListener("submit", function(e) {
      e.preventDefault(); // Stop default form behavior

      const formData = new FormData(this);
      const jsonData = {};

      formData.forEach((value, key) => {
        if(key == 'foodItem') {
          key = 'title';
        } else if(key.toLowerCase().includes('folate') || key.toLowerCase().includes('folic')) {
          key = 'folate_folic_acid';
        } else if (value == '' || value == null) {
          return;
        }
        jsonData[key] = value;
      });

      fetch("/add-food", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(jsonData)
      })
      .then(response => response.json())
      .then(data => {
        // Show the alert
        if(data.response.includes("Success")) {
          alertEl.classList.add("alert-info");
          alertEl.classList.remove("alert-danger");
          infoBox.textContent = data.response;
          document.querySelectorAll('.auto-scraped').forEach(element => {
            element.remove();
          });
          removeButton.style.display = "none";
          linkInput.value = "";
        } else {
          alertEl.classList.remove("alert-info");
          alertEl.classList.add("alert-danger");
          infoBox.textContent = data.response;
        }

        alertEl.style.display = "block";
        alertEl.classList.add("show");

        // Auto-close after 3 seconds
        setTimeout(() => {
          alertEl.classList.remove("show");
          alertEl.classList.add("hide");
          setTimeout(() => {
            alertEl.style.display = "none";
            alertEl.classList.remove("hide");
          }, 500); // Delay to finish transition
        }, 5000);
      })
      .catch(error => {
        console.error("Error:", error);
      });
    });
  </script>
</body>
</html>