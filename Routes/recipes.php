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
      <form id="addFoodForm">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 id="form-label-text" class="mb-0">Add New Food</h4>
          <button type="button" onclick="cleanUpForms()" class="btn btn-danger" id="removeFoodFields" style="display: none;">
            Clear Fields
          </button>
        </div>

        <div class="form-container">
          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="foodItem" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Link or Food Name:</label>
            <input type="text" class="form-control" id="foodItem" name="foodItem" required style="width: 100%;">
          </div>
          <hr class="hr-blurry my-3 form-divider">
          <div id="nutritionDropdown" class="dropdown mr-3 mb-3 hide-on-update">
            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="nutritionDropdownBtn" style="width: 100%;">
              Add Nutrition Info
            </button>
            <div class="custom-nutrition-dropdown" id="nutritionDropdownContainer" style="display: none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 80vh;">
              <input type="text" class="form-control mb-2 ignore" id="nutritionSearchInput" placeholder="Search items..." style="position: sticky; top: 0; z-index: 20; background-color: white;">
              <ul id="nutritionDropdownMenu" class="list-group">
                <!-- Filtered items will appear here -->
              </ul>
            </div>
          </div>

          <div class="d-flex justify-content-between" id="close-submit">
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
    <div id="foodSearchDropdown" class="dropdown m-3" style="display: none;">
      <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="foodDropdownBtn">
        Select Item
      </button>
      <div class="custom-dropdown" id="foodDropdownContainer" style="display:none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 80vh;">
        <input type="text" class="form-control mb-2" id="foodSearchInput" placeholder="Search items..." style="position: sticky; top: 0; z-index: 20; background-color: white;">
        <ul id="foodDropdownMenu" class="list-group">
          <!-- Filtered items will appear here -->
        </ul>
      </div>
    </div>
	</div>

  <script>
    //Add: button next to items to remove just that one, like a red X
    //move food addition to recipe making
    let foodDBItems;
    let debounceTimer;
    let preventBlur = false;

    const foodDropdownBtn = document.getElementById('foodDropdownBtn');
    const foodDropdownContainer = document.getElementById('foodDropdownContainer');
    const foodSearchInput = document.getElementById('foodSearchInput');
    const foodDropdownMenu = document.getElementById('foodDropdownMenu');
    const foodSearchDropdown = document.getElementById('foodSearchDropdown')

    const nutritionDropdownBtn = document.getElementById('nutritionDropdownBtn');
    const nutritionDropdownContainer = document.getElementById('nutritionDropdownContainer');
    const nutritionSearchInput = document.getElementById('nutritionSearchInput');
    const nutritionDropdownMenu = document.getElementById('nutritionDropdownMenu');
    
    const foodLinkInput = document.getElementById("foodItem");
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const clearFoodFields = document.getElementById('removeFoodFields');

    const alertEl = document.getElementById("autoAlert");
    const infoBox = document.getElementById('autoAlertInfo');
    
    const addFoodForm = document.getElementById("addFoodForm");

    const nutritionList = [
      {title: "Total Servings"},
      {title: "Serving Size"},
      {title: "Serving Unit"},
      {title: "Calories"},
      {title: "Total Fat"},
      {title: "Saturated Fat"},
      {title: "Trans Fat"},
      {title: "Cholesterol"},
      {title: "Sodium"},
      {title: "Total Carbohydrate"},
      {title: "Dietary Fiber"},
      {title: "Sugars"},
      {title: "Added Sugars"},
      {title: "Protein"},
      {title: "Vitamin D"},
      {title: "Calcium"},
      {title: "Iron"},
      {title: "Potassium"},
      {title: "Vitamin A"},
      {title: "Vitamin C"},
      {title: "Vitamin E"},
      {title: "Vitamin K"},
      {title: "Thiamin"},
      {title: "Riboflavin"},
      {title: "Niacin"},
      {title: "Vitamin B6"},
      {title: "Folate or Folic Acid"},
      {title: "Vitamin B12"},
      {title: "Biotin"},
      {title: "Phosphorus"},
      {title: "Iodine"},
      {title: "Magnesium"},
      {title: "Zinc"},
      {title: "Copper"},
      {title: "Manganese"},
      {title: "Chloride"},
      {title: "Chromium"},
      {title: "Molybdenum"},
      {title: "Choline"},
      {title: "Pantothenic Acid"},
      {title: "Selenium"},
      {title: "Price"},
      {title: "HTML Link"}
    ];

    //Change this to be in it's correct spot, when adding recipes, and learn how to cache the results.
    window.addEventListener('DOMContentLoaded', () => {
      getFoodFromDB();
      // foodSearchDropdown.style.display = 'block';
    });

    document.addEventListener("keydown", function(event) {
      if (event.key === "Escape") {
        const openForms = document.querySelectorAll(".popup");

        openForms.forEach(form => {
          // You can add additional logic here if needed
          form.style.display = "none";
        });
      }
    });

    nutritionDropdownBtn.addEventListener('click', () => {
      nutritionDropdownBtn.style.display = 'none';
      nutritionDropdownContainer.style.display = 'block';
      nutritionSearchInput.focus();
      const inputIds = Array.from(document.querySelectorAll('input.manual-scraped, input.auto-scraped'))
        .map(input => input.id.toLowerCase().replace(/ /g, "_"))
        .filter(id => id); // filters out undefined or empty IDs

      const filtered = nutritionList.filter(item => !inputIds.includes(item.title.toLowerCase().replace(/ /g, "_")));
      populateDropdown(filtered, nutritionDropdownMenu, nutritionDropdownBtn, nutritionDropdownContainer);
    });

    nutritionSearchInput.addEventListener('input', () => {
      const query = nutritionSearchInput.value.toLowerCase();
      const inputIds = Array.from(document.querySelectorAll('input.manual-scraped, input.auto-scraped'))
        .map(input => input.id.toLowerCase().replace(/ /g, "_"))
        .filter(id => id);
      const filtered = nutritionList.filter(item => item.title.toLowerCase().includes(query)).filter(item => !inputIds.includes(item.title.toLowerCase().replace(/ /g, "_")));
      populateDropdown(filtered, nutritionDropdownMenu, nutritionDropdownBtn, nutritionDropdownContainer);
    });

    nutritionSearchInput.addEventListener('blur', () => {
      if (!preventBlur) {
        nutritionDropdownBtn.style.display = 'inline-block';
        nutritionDropdownContainer.style.display = 'none';
      }
      preventBlur = false; // Reset after blur fires
      nutritionSearchInput.value = "";
    });

    addFoodForm.addEventListener("submit", async function(e) {
      e.preventDefault(); // Stop default form behavior

      const formData = new FormData(this);
      const jsonDataFood = {};

      formData.forEach((value, key) => {
        if(key == 'foodItem') {
          key = 'title';
        } else if(key.toLowerCase().includes('folate') || key.toLowerCase().includes('folic')) {
          key = 'folate_folic_acid';
        } else if (value == '' || value == null) {
          return;
        }
        formatted = key.toLowerCase().replace(/ /g, "_");
        jsonDataFood[formatted] = value;
      });


      const lowerCaseFoodDBItems = foodDBItems.map(item => item.title.toLowerCase());
      const isMatch = lowerCaseFoodDBItems.includes(jsonDataFood['title'].toLowerCase());
      let isValidURL = checkURL(jsonDataFood.title, false);

      if(isValidURL.success) {
        let alert = showAlertBox("Please wait for URL to resolve before submitting");
        return;
      }

      if(isMatch) {
        const editFoodChoice = await showConfirmationBox(jsonDataFood['title']+" is already added to the database, do you want to edit it, or cancel?");
        if (editFoodChoice) {
          // console.log("Success");
          editDBItem("/edit-food", jsonDataFood)
          return;
        } else {
          console.log("Cancelled");
          return;
        }
      }
      
      const result = await pushDBItemNew("/add-food", jsonDataFood);
      if(result) getFoodFromDB();
    });

    foodLinkInput.addEventListener("input", (event) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const value = event.target.value.trim();

        // Check if input is empty
        if (!value) {
          // console.log("Input cleared. Skipping fetch.");
          document.querySelectorAll('.auto-scraped').forEach(element => {
            element.remove();
          });
          return;
        }

        clearFoodFields.style.display = 'inline-block';

        // Check if value is a valid URL
        if (!checkURL(value, false).success) return;

        document.querySelectorAll('.auto-scraped').forEach(element => {
          element.remove();
        });

        fetch('/proxy?url=' + encodeURIComponent(value))
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

    foodSearchInput.addEventListener('blur', () => {
      if (!preventBlur) {
        foodDropdownBtn.style.display = 'inline-block';
        foodDropdownContainer.style.display = 'none';
      }
      preventBlur = false; // Reset after blur fires
      foodSearchInput.value = "";
    });

    foodDropdownBtn.addEventListener('click', () => {
      foodDropdownBtn.style.display = 'none';
      foodDropdownContainer.style.display = 'block';
      foodSearchInput.focus();
      populateDropdown(foodDBItems, foodDropdownMenu, foodDropdownBtn, foodDropdownContainer);
    });

    foodSearchInput.addEventListener('input', () => {
      const query = foodSearchInput.value.toLowerCase();
      const filtered = foodDBItems.filter(item => item.title.toLowerCase().includes(query));
      populateDropdown(filtered, foodDropdownMenu, foodDropdownBtn, foodDropdownContainer);
    });

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      if(toggleBtn.innerHTML.includes('☰')) {
        toggleBtn.innerHTML = '>'
      } else {
        toggleBtn.innerHTML = '☰'
      }
    });

    function populateDropdown(list, dropdownMenu, dropdownBtn, dropdownContainer) {
      const parentDiv = document.getElementById('foodForm');
      const formContainer = parentDiv.querySelector('.form-container');
      const referenceChild = formContainer.querySelector('.dropdown');

      dropdownMenu.innerHTML = '';
      if (list.length === 0) {
        dropdownMenu.innerHTML = '<li class="list-group-item text-muted">No results</li>';
        return;
      }

      list.forEach(item => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.textContent = item.title;
        li.style.cursor = 'pointer';
        li.id = item.title;

        const wrapper = document.createElement('div');
        wrapper.className = 'mb-1 d-flex align-items-center flex-nowrap manual-scraped';
        wrapper.style.maxWidth = '100%';

        //<span class="text-danger" style="cursor:pointer; font-weight:bold;">&times;</span>
        const span = document.createElement('span');
        span.className = 'text-danger manual-scraped fs-2 text-center mb-2';
        span.style.cursor = 'pointer';
        span.style.fontWeight = 'bold';
        span.innerHTML = '&times;';
        span.addEventListener('click', () => {
          wrapper.remove();
          const inputIds = Array.from(document.querySelectorAll('input.manual-scraped'))
            .map(input => input.id)
            .filter(id => id); // filters out undefined or empty IDs
          // console.log(inputIds.length);
          if(clearFoodFields.style.display == 'inline-block' && inputIds.length == 0) clearFoodFields.style.display = "none";
        });

        const verticalRule = document.createElement('div');
        verticalRule.className = 'vr';

        const label = document.createElement('label');
        label.setAttribute('for', item.title);
        label.className = 'form-label me-2 mb-0 text-nowrap manual-scraped';
        label.style.width = '140px';
        label.textContent = item.title;

        const input = document.createElement('input');
        input.type = 'text';
        input.id = item.title;
        input.name = item.title;
        input.required = false;
        input.className = 'form-control manual-scraped';
        input.style.width = '100%';

        wrapper.append(span, verticalRule, label, input);

        li.addEventListener('mousedown', () => {
          preventBlur = true; // Prevent blur from hiding dropdown
        });

        li.addEventListener('click', () => {
          // dropdownBtn.textContent = item.title;
          dropdownBtn.style.display = 'inline-block';
          dropdownContainer.style.display = 'none';
          if (referenceChild && referenceChild.nextSibling) {
            formContainer.insertBefore(wrapper, referenceChild.nextSibling);
          } else {
            // If there's no next sibling, just append at the end
            formContainer.appendChild(wrapper);
          }
          const inputIds = Array.from(document.querySelectorAll('input.manual-scraped'))
            .map(input => input.id)
            .filter(id => id); // filters out undefined or empty IDs
          // console.log(inputIds.length);
          if(clearFoodFields.style.display == 'none' && inputIds.length > 0) clearFoodFields.style.display = "inline-block";
          input.focus();
        });

        dropdownMenu.appendChild(li);
      });
    }

    function openForm(formID) {
      const allForms = document.querySelectorAll('.popup');
      allForms.forEach(element => {
        element.style.display = 'none';
      })
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

    function renderNutrition(inputArray) {
      if (!Array.isArray(inputArray)) {
        console.warn("Unexpected response format");
        return;
      }

      const parentDiv = document.getElementById('foodForm');
      const formContainer = parentDiv.querySelector('.form-container');
      const referenceChild = formContainer.querySelector('.form-divider');

      foodLinkInput.value = inputArray[0]['product'];
      clearFoodFields.style.display = 'inline-block';

      inputArray.filter(item => item.nutrient).reverse().forEach(item => {
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

    function getFoodFromDB() {
      foodDBItems = [];
      fetch('/get-food')
        .then(response => response.json())
        .then(data => {
          data.foodItems.forEach( food => {
            foodDBItems.push(food);
          });
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    }

    function cleanUpForms() {
      document.querySelectorAll('.auto-scraped').forEach(element => {
        element.remove();
      });
      document.querySelectorAll('.manual-scraped').forEach(element => {
        element.value = '';
      });
      clearFoodFields.style.display = "none";
      foodLinkInput.value = "";
    }

    function showAlertBox(alertText) {
      var result;
      switch (true) {
        case alertText.includes("Success"):
          alertEl.classList.add("alert-info");
          alertEl.classList.remove("alert-danger");
          result = true;
          break;
        case alertText.includes("temperature"):
          console.log("Temperature issue");
          break;
        default:
          alertEl.classList.remove("alert-info");
          alertEl.classList.add("alert-danger");
          result = false;
      }
      infoBox.textContent = alertText;
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
      return result;
    }

    async function showConfirmationBox(message) {
      return new Promise(resolve => {
        const modal = document.createElement('div');
        modal.innerHTML = `
          <div class="modal fade" tabindex="-1" inert>
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header ${message.includes("Success") ? 'bg-info' : 'bg-danger'} text-white">
                  <h5 class="modal-title">Confirm</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">${message}</div>
                <div class="modal-footer">
                  <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button class="btn btn-primary">Confirm</button>
                </div>
              </div>
            </div>
          </div>
        `;
        document.body.appendChild(modal);

        const modalEl = modal.querySelector('.modal');
        modalEl.removeAttribute('inert');

        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();

        modal.querySelector('.btn-primary').onclick = () => {
          resolve(true);
          bsModal.hide();
        };
        modal.querySelector('.btn-secondary').onclick = () => {
          resolve(false);
          bsModal.hide();
        };

        modalEl.addEventListener('hidden.bs.modal', () => {
          modalEl.setAttribute('inert', '');
          modal.remove();
        });
      });
    }

    function checkURL(inputURL, failCase) {
      /*
        If it is a valid URL, and failcase is true, the function fails
        If it is not a valid URL and failcase is true, the function passes
        If it is a valid URL and failcase is false, the function passes
        If it is not a valid URL and failcase is false, the function fails
      */
      let success = false;
      let message = "";
      try {
        new URL(inputURL);
        success = true;
      } catch (err) {
        message = err.message;
      }
      if (failCase) success = !success;
      return {
        'success': success,
        'message': message
      };
    }

    async function pushDBItemNew(route, jsonData) {
      let success = false;
      try {
        const response = await fetch(route, {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(jsonData)
        });

        const data = await response.json();
        success = showAlertBox(data.response);
        if(success) cleanUpForms();
        return success; // Only return true after successful completion
      } catch (error) {
        console.error("Error:", error);
        return success; // Return false if something goes wrong
      }
    }

    async function editDBItem(route, jsonData) {
      let success = false;
      try {
        const response = await fetch(route, {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(jsonData)
        });

        const data = await response.json();
        success = showAlertBox(data.response);
        console.log(data.response);
        if(success) cleanUpForms();
        return success; // Only return true after successful completion
      } catch (error) {
        console.error("Error:", error);
        return success; // Return false if something goes wrong
      }
    }
  </script>
</body>
</html>