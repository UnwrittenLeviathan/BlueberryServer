<!-- Currently switching over all xxxxxxmenuItems to menuItem and make it flexible -->
<!DOCTYPE html>
<html lang="en" translate="no">
<head>
	<?php include 'head.php';?>
</head>
<body style="background-color: #ecf2f9;">
	<?php include 'body-header.php'; ?>
  <div id="autoAlert" class="alert alert-info alert-dismissible fade position-fixed top-0 start-50 translate-middle-x mt-3" role="alert" style="z-index: 1050; display: none;">
    <strong>Info:</strong>
  </div>

	<div class="d-flex">
		<!-- Popup Form -->
		<div id="recipeForm" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup form-holder" style="display: none; z-index: 10; resize: both; overflow: auto; max-height: 80vh;">
      <form class="searchable-form multi-page-form" onsubmit="handleAddRecipeFormSubmit(event)">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 id="form-label-text" class="mb-0">Add New Recipe</h4>
          <button type="button" onclick="cleanUpForms()" class="btn btn-danger" id="removeFields" style="display: none;">
            Clear All Fields
          </button>
          <button type="button" onclick="cleanUpFormsComplete()" class="btn btn-danger" id="removeFieldsComplete" style="display: none;">
            Remove All Fields
          </button>
        </div>

        <!-- Page 1 -->
        <div id="formPage1" class="form-page">
          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="title" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Name Of Dish</label>
            <input type="text" class="form-control" id="title" name="title" required style="width: 100%;">
          </div>

          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="servings" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Servings</label>
            <input type="text" class="form-control" id="servings" name="servings" required style="width: 100%;">
          </div>

          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="description" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Description</label>
            <textarea class="form-control" id="description" name="description" rows="5" placeholder="Optionally add a description of the recipe, instructions are added later..."></textarea>
          </div>

          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="closeForm('recipeForm')">Close</button>
            <button type="button" class="btn btn-primary nextBtn" onclick="validatePage(this, 2)">Next</button>
          </div>
        </div>
        <!-- Page 2 -->
        <div id="formPage2" class="form-page form-container" style="display: none;">
          <!-- <div class="mb-3 d-flex align-items-center justify-content-center flex-nowrap">
            <h5 class="form-label-text me-2 mb-0 text-nowrap" style="width: 140px;">Ingredients List</h5>
          </div> -->
          <div class="dropdown mr-3 mb-3 hide-on-update">
            <button class="btn btn-primary dropdown-toggle dropdownBtn" onclick="handleDropdown.bind(this, foodDBItems, true, true)()" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 100%;">
              Click to Search Available Food
            </button>
            <div class="dropdownContainer" style="display: none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 25vh;">
              <input type="text" class="form-control mb-2 ignore searchInput" oninput="handleSearchDropdown.bind(this, foodDBItems, true, true)()" onblur="handleSearchDropdownBlur.call(this)" placeholder="Search items..." style="position: sticky; top: 0; z-index: 20; background-color: white;">
              <ul class="list-group dropdownMenu">
                <!-- Filtered items will appear here -->
              </ul>
            </div>
          </div>
          <div class="dropdown mr-3 mb-3 hide-on-update">
            <button class="btn btn-primary dropdown-toggle dropdownBtn" onclick="handleDropdown.bind(this, recipeDBItems, true, true)()" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 100%;">
              Click to Search Available Recipes
            </button>
            <div class="dropdownContainer" style="display: none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 25vh;">
              <input type="text" class="form-control mb-2 ignore searchInput" oninput="handleSearchDropdown.bind(this, recipeDBItems, true, true)()" onblur="handleSearchDropdownBlur.call(this)" placeholder="Search items..." style="position: sticky; top: 0; z-index: 20; background-color: white;">
              <ul class="list-group dropdownMenu">
                <!-- Filtered items will appear here -->
              </ul>
            </div>
          </div>
          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="showPage('recipeForm', 1)">Back</button>
            <button type="button" class="btn btn-primary nextBtn" onclick="validatePage(this, 3)">Next</button>
          </div>
        </div>
        <!-- Page 3 -->
        <div id="formPage3" class="form-page form-container" style="display: none; max-height: 50vh;">
          <div id="instruction-list" class="instruction-list mr-3 mb-3">
            <div class="mb-3 d-flex align-items-center flex-nowrap instruction" id="step1" style="max-width: 100%;">
              <label for="step-1" class="form-label me-2 mb-0 text-nowrap justify-content-center" style="width: 140px;">Step 1</label>
              <textarea class="form-control" id="step-1" rows="3" name="step-1" placeholder="Add in your instruction here for step 1..."></textarea>
            </div>
            <button class="btn btn-primary" id="next-step-button" type="button" style="width: 100%;" onclick="addStep.call(this)">
              Add Another Step
            </button>
          </div>


          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="showPage('recipeForm', 2)">Back</button>
            <button type="submit" class="btn btn-success">Submit</button>
          </div>
        </div>
      </form>
    </div>
    <div id="foodForm" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup" style="display: none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 80%;">
      <form class="searchable-form" onsubmit="handleAddFoodFormSubmit(event)">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 id="form-label-text" class="mb-0">Add New Food</h4>
          <button type="button" onclick="cleanUpForms()" class="btn btn-danger removeFields" style="display: none;">
            Clear Fields
          </button>
        </div>

        <div class="form-container">
          <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
            <label for="foodItem" class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Food Name:</label>
            <input type="text" class="form-control" id="foodItem" name="foodItem" required style="width: 100%;">
          </div>
          <hr class="hr-blurry my-3 form-divider">
          <div class="dropdown mr-3 mb-3 hide-on-update">
            <button class="btn btn-primary dropdown-toggle dropdownBtn" onclick="handleDropdown.bind(this, nutritionList)()" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 100%;">
              Add Nutrition Info
            </button>
            <div class="dropdownContainer" style="display: none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 80vh;">
              <input type="text" class="form-control mb-2 ignore searchInput" oninput="handleSearchDropdown.bind(this, nutritionList)()" onblur="handleSearchDropdownBlur.call(this)" placeholder="Search items..." style="position: sticky; top: 0; z-index: 20; background-color: white;">
              <ul class="list-group dropdownMenu">
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
    <div id="fridgeForm" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup" style="display: none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 80%;">
      <form class="searchable-form" onsubmit="handleAddFoodToFridgeFormSubmit(event)">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 id="form-label-text" class="mb-0">Add Food to Fridge</h4>
          <button type="button" onclick="cleanUpFormsComplete()" class="btn btn-danger removeFields" style="display: none;">
            Clear Fields
          </button>
        </div>

        <div class="form-container">
          <div class="dropdown mr-3 mb-3 hide-on-update">
            <button class="btn btn-primary dropdown-toggle dropdownBtn" onclick="handleDropdown.bind(this, foodDBItems, withExtras=true)()" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 100%;">
              Click to Search Available Food
            </button>
            <div class="dropdownContainer" style="display: none; z-index: 10; min-width: 350px; resize: both; overflow: auto; max-height: 80vh;">
              <input type="text" class="form-control mb-2 ignore searchInput" oninput="handleSearchDropdown.bind(this, foodDBItems, withExtras=true)()" onblur="handleSearchDropdownBlur.call(this)" placeholder="Search items..." style="position: sticky; top: 0; z-index: 20; background-color: white;">
              <ul class="list-group dropdownMenu">
                <!-- Filtered items will appear here -->
              </ul>
            </div>
          </div>

          <div class="d-flex justify-content-between" id="close-submit">
            <button type="button" class="btn btn-secondary" onclick="closeForm('fridgeForm')">Close</button>
            <button type="submit" class="btn btn-success">Submit</button>
          </div>
        </div>
      </form>
    </div>
    <div id="recipeDisplay" class="position-fixed top-50 start-50 translate-middle bg-white border p-4 rounded shadow-lg popup form-holder popup-display" style="display: none; z-index: 10; resize: both; overflow: auto; max-height: 80vh; width: 50vw;">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 id="form-label-text" class="mb-0"></h4>
        <button type="button" onclick="editRecipe()" class="btn btn-info">
          Edit Recipe
        </button>
      </div>
      <hr class="hr-blurry my-3 form-divider">

      <!-- Page 1 -->
      <div id="formPage1" class="form-page">
        <div class="mb-3 d-flex align-items-center justify-content-between flex-nowrap" style="max-width: 100%;">
          <h3 id="dishName" class="form-label me-2 mb-0 text-nowrap">Name Of Dish</h3>
          <div style="flex: 0 0 10%;"></div>
          <h4 id="dishServings" class="form-label me-2 mb-0 text-nowrap">Servings</h4>
        </div>        

        <div id="dishDescription" class="mb-3 d-flex align-items-center" style="max-width: 100%;">
          
        </div>
        <hr class="hr-blurry my-3 form-divider">

        <div class="mb-3 d-flex align-items-center flex-nowrap" style="max-width: 100%;">
          <h4  class="form-label me-2 mb-0 text-nowrap" style="width: 140px;">Ingredient List</h4>
        </div>

        <div id="dishIngredients" class="mb-3 d-flex align-items-center flex-column justify-content-between" style="max-width: 100%;">
          
        </div>

        <hr class="hr-blurry my-3 form-divider">
        
        <div class="d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" onclick="closeForm('recipeDisplay')">Close</button>
          <button type="button" class="btn btn-primary nextBtn" onclick="showPage('recipeDisplay', 2)">Next</button>
        </div>
      </div>
      <!-- Page 2 -->
      <div id="formPage2" class="form-page" style="display: none;">
        <div id="dishInstructions" class="mb-3 d-flex flex-column justify-content-between flex-wrap" style="max-width: 100%;">

        </div>

        <div class="d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" onclick="showPage('recipeDisplay', 1)">Back</button>
        </div>
      </div>
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

      <button class="btn btn-success mb-2" onclick="openForm('fridgeForm')">
        <i class="bi bi-gear"></i>
        <span class="btn-label">Add Food to Fridge</span>
      </button>
    </div>

    <!-- Use this div to have card-like sections for each recipe, with a blue background, rounded, for the top with the title, then in the middle have a
      white background with the ingredient list, or start of it, and when any part of it is clicked on, open up the full instruction/ food list and have an edit button. -->
    <div class="container-fluid p-3">
      <div id="grid-container" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        <!-- Add more <div class="col"> blocks for additional cards -->
      </div>
    </div>

	</div>

  <script>
    //Add: button next to items to remove just that one, like a red X
    //move food addition to recipe making
    let foodDBItems = [];
    let recipeDBItems = [];
    let ingredientList = [];
    let instructionList = [];

    let debounceTimer;
    let preventBlur = false;
    
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const foodLinkInput = document.getElementById("foodItem");

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

    const maxNutritionValues = {
      "Total_Fat": {
        unit: "gram",
        amount: 80
      },
      "Saturated_Fat": {
        unit: "gram",
        amount: 20
      },
      "Cholesterol": {
        unit: "milligram",
        amount: 300
      },
      "Sodium": {
        unit: "milligram",
        amount: 2300
      },
      "Total_Carbohydrates": {
        unit: "gram",
        amount: 275
      },
      "Dietary_Fiber": {
        unit: "gram",
        amount: 28
      },
      "Sugars": {
        unit: "gram",
        amount: 50
      },
      "Protein": {
        unit: "gram",
        amount: 50
      },
      "Vitamin_D": {
        unit: "microgram",
        amount: 20
      },
      "Calcium": {
        unit: "milligram",
        amount: 1300
      },
      "Iron": {
        unit: "milligram",
        amount: 18
      },
      "Potassium": {
        unit: "milligram",
        amount: 4700
      },
      "Vitamin_A": {
        unit: "microgram",
        amount: 900
      },
      "Vitamin_C": {
        unit: "milligram",
        amount: 90
      },
      "Vitamin_E": {
        unit: "milligram",
        amount: 15
      },
      "Vitamin_K": {
        unit: "microgram",
        amount: 120
      },
      "Thiamin": {
        unit: "milligram",
        amount: 1.2
      },
      "Riboflavin": {
        unit: "milligram",
        amount: 1.3
      },
      "Niacin": {
        unit: "milligram",
        amount: 16
      },
      "Vitamin_B6": {
        unit: "milligram",
        amount: 1.7
      },
      "Folate_Folic_Acid": {
        unit: "microgram",
        amount: 400
      },
      "Vitamin_B12": {
        unit: "microgram",
        amount: 2.4
      },
      "Biotin": {
        unit: "microgram",
        amount: 30
      },
      "Phosphorus": {
        unit: "milligram",
        amount: 1250
      },
      "Iodine": {
        unit: "microgram",
        amount: 150
      },
      "Magnesium": {
        unit: "milligram",
        amount: 420
      },
      "Zinc": {
        unit: "milligram",
        amount: 11
      },
      "Copper": {
        unit: "milligram",
        amount: 0.9
      },
      "Manganese": {
        unit: "milligram",
        amount: 2.3
      },
      "Chloride": {
        unit: "milligram",
        amount: 2300
      },
      "Chromium": {
        unit: "microgram",
        amount: 35
      },
      "Molybdenum": {
        unit: "microgram",
        amount: 45
      },
      "Choline": {
        unit: "milligram",
        amount: 550
      },
      "Pantothenic_Acid": {
        unit: "milligram",
        amount: 5
      },
      "Selenium": {
        unit: "microgram",
        amount: 55
      }
    }

    //Change this to be in it's correct spot, when adding recipes, and learn how to cache the results.
    window.addEventListener('DOMContentLoaded', async () => {
      getFoodFromDB();
      // console.log(foodDBItems);
      await getRecipesFromDB();
      // console.log(maxNutritionValues);
      // console.log(document.querySelectorAll('.form-page'));
      // foodSearchDropdown.style.display = 'block';
    });

    //Add event listener to each form page where if not on the last page, go to the next page if the enter key is hit
    document.querySelectorAll('.multi-page-form').forEach(el => {
      let pageCount = el.querySelectorAll('.form-page').length;
      el.querySelectorAll('.form-page').forEach((page, index) => {
        if(pageCount-1 == index) return;
        let currentPage = parseInt(page.id.match(/\d+/g));
        let nextButton = page.querySelector('.nextBtn');
        page.addEventListener('keydown', (e) => {
          if(e.key == 'Enter') {
            e.preventDefault();
            nextButton.click();
          }
        });
      });
    });

    // document.querySelectorAll('.popup-display').forEach(el => {
    //   el.addEventListener('keydown', (e) => {
    //     if(e.key == 'ArrowLeft') {

    //     }
    //   });
    // });

    document.addEventListener("keydown", function(event) {
      if (event.key === "Escape") {
        const openForms = document.querySelectorAll(".popup");

        openForms.forEach(form => {
          // You can add additional logic here if needed
          form.style.display = "none";
        });
      }
    });

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      if(toggleBtn.innerHTML.includes('☰')) {
        toggleBtn.innerHTML = '>'
      } else {
        toggleBtn.innerHTML = '☰'
      }
    });

    // foodLinkInput.addEventListener("input", function (event) {
    //   clearTimeout(debounceTimer);
    //   const parentEl = this.closest('.searchable-form');
    //   clearFoodFields = parentEl.querySelector('.removeFields');
    //   debounceTimer = setTimeout(() => {
    //     const value = this.value.trim();

    //     // Check if input is empty
    //     if (!value) {
    //       // console.log("Input cleared. Skipping fetch.");
    //       document.querySelectorAll('.auto-scraped').forEach(element => {
    //         element.remove();
    //       });
    //       return;
    //     }

    //     clearFoodFields.style.display = 'inline-block';

    //     // Check if value is a valid URL
    //     if (!checkURL(value, false).success) return;

    //     document.querySelectorAll('.auto-scraped').forEach(element => {
    //       element.remove();
    //     });

    //     fetch('/proxy?url=' + encodeURIComponent(value))
    //       .then(res => res.json())
    //       .then(data => {
    //           data.push({
    //             nutrient: 'Html Link',
    //             amount: value,
    //           });
    //         renderNutrition(data);
    //       });
    //   }, 800);
    // });

    function validatePage(button, nextPage) {
      const form = button.closest('.form-holder')
      const formPage = button.closest('.form-page');
      const requiredFields = formPage.querySelectorAll('input[required], select[required], textarea[required]');
      let valid = true;

      requiredFields.forEach(field => {
        if (!field.value.trim()) {
          field.classList.add('is-invalid');
          valid = false;
        } else {
          field.classList.remove('is-invalid');
        }
      });

      if (valid) {
        showPage(form.id, nextPage);
      }
    }

    function handleDropdown(searchArray, withExtras = false, required = false) {
      // console.log(this);
      const parentEl = this.closest('.dropdown');
      const dropdownContainer = parentEl.querySelector('.dropdownContainer');
      const searchInput = parentEl.querySelector('.searchInput');

      this.style.display = 'none';
      dropdownContainer.style.display = 'block';
      searchInput.focus();
      const inputIds = Array.from(document.querySelectorAll('input.manual-scraped, input.auto-scraped'))
        .map(input => input.id.toLowerCase().replace(/ /g, "_"))
        .filter(id => id); // filters out undefined or empty IDs

      const filtered = searchArray.filter(item => !inputIds.includes(item.title.toLowerCase().replace(/ /g, "_")));
      populateDropdown(parentEl, filtered, withExtras, required);
    }

    function handleSearchDropdown(searchArray, withExtras = false, required = false) {
      const parentEl = this.closest('.dropdown');
      const query = this.value.toLowerCase();
      const inputIds = Array.from(document.querySelectorAll('input.manual-scraped, input.auto-scraped'))
        .map(input => input.id.toLowerCase().replace(/ /g, "_"))
        .filter(id => id);
      const filtered = searchArray.filter(item => item.title.toLowerCase().includes(query)).filter(item => !inputIds.includes(item.title.toLowerCase().replace(/ /g, "_")));
      populateDropdown(parentEl, filtered, withExtras, required);
    }

    function handleSearchDropdownBlur() {
      const parentEl = this.closest('.dropdown');
      const dropdownContainer = parentEl.querySelector('.dropdownContainer');
      const searchInput = parentEl.querySelector('.searchInput');
      const parentForm = this.closest('.searchable-form');
      const dropdownBtn = parentEl.querySelector('.dropdownBtn');

      if (!preventBlur) {
        dropdownBtn.style.display = 'inline-block';
        dropdownContainer.style.display = 'none';
      }
      preventBlur = false; // Reset after blur fires
      searchInput.value = "";
    }

    function addStep() {
      const parentEl = this.closest('.instruction-list');
      const steps = parentEl.querySelectorAll('.instruction');
      const lastStepEl = steps[steps.length - 1];
      const lastStepId = lastStepEl ? lastStepEl.id : null;
      const lastStep = lastStepId.match(/\d+/g);
      const nextStep = parseInt(lastStep)+1;

      // console.log(nextStep);

      const wrapper = document.createElement('div');
      wrapper.className = 'mb-3 d-flex align-items-center flex-nowrap instruction added-step step'+nextStep;
      wrapper.id = 'step'+nextStep;
      wrapper.style.maxWidth = '100%';

      const label = document.createElement('label');
      label.className = 'form-label me-2 mb-0 text-nowrap justify-content-center'
      label.target = 'step-'+nextStep;
      label.style.width = '140px';
      label.innerHTML = 'Step '+nextStep;

      const textArea = document.createElement('textarea');
      textArea.className = 'form-control'
      textArea.name = 'step-'+nextStep;
      textArea.id = 'step-'+nextStep;
      textArea.rows = '3';
      textArea.placeholder = "Add in your instruction here for step "+nextStep+"...";

      const span = document.createElement('span');
      span.className = 'text-danger manual-scraped fs-2 text-center mb-2';
      span.style.cursor = 'pointer';
      span.style.fontWeight = 'bold';
      span.innerHTML = '&times;';
      span.addEventListener('click', () => {
        wrapper.remove();
      });

      wrapper.append(label, textArea, span);

      parentEl.insertBefore(wrapper, this);
      this.focus();
    }

    async function handleAddFoodToFridgeFormSubmit(event) {
      event.preventDefault();

      const form = event.target; // The form element
      const formData = new FormData(form);
      const jsonDataFood = [];

      formData.forEach((value, key) => {
        jsonDataFood.push({
          item: key,
          amount: value,
        });
      });

      console.log(jsonDataFood);
    }

    async function handleAddFoodFormSubmit(event) {
      event.preventDefault(); // Stop default form behavior

      const form = event.target; // The form element
      const formData = new FormData(form);
      const jsonDataFood = {};

      formData.forEach((value, key) => {
        if (key === 'foodItem') {
          key = 'title';
        } else if (key.toLowerCase().includes('folate') || key.toLowerCase().includes('folic')) {
          key = 'folate_folic_acid';
        } else if (value === '' || value == null) {
          return;
        }
        const formatted = key.toLowerCase().replace(/ /g, "_");
        jsonDataFood[formatted] = value;
      });

      const lowerCaseFoodDBItems = foodDBItems.map(item => item.title.toLowerCase());
      const isMatch = lowerCaseFoodDBItems.includes(jsonDataFood['title'].toLowerCase());
      const isValidURL = checkURL(jsonDataFood.title, false);

      if (isValidURL.success) {
        showAlertBox("Please wait for URL to resolve before submitting");
        return;
      }

      if (isMatch) {
        const editFoodChoice = await showConfirmationBox(
          `${jsonDataFood['title']} is already added to the database, do you want to edit it, or cancel?`
        );
        if (!editFoodChoice) {
          return;
        }
      }

      const result = await pushDBItemNew("/add-food", jsonDataFood);
      if (result) {
        getFoodFromDB();
        cleanUpForms();
      }
    }

    async function handleAddRecipeFormSubmit(event) {
      event.preventDefault(); // Stop default form behavior

      const form = event.target; // The form element
      const formData = new FormData(form);
      const titleValue = formData.get('title');
      const jsonData = {};
      jsonData['food'] = [];
      jsonData['instruction'] = [];
      const formPage2 = form.querySelector("#formPage2");
      const formPage3 = form.querySelector("#formPage3");

      const ingredients = new Set();
      const instructions = new Set();
      const recipes = new Set();

      const removeFields = document.getElementById('removeFieldsComplete');
      removeFields.style.display = "none";

      // Collect all input/select/textarea elements inside #formPage2
      if (formPage2) {
        const inputs = formPage2.querySelectorAll("input, select, textarea");
        inputs.forEach(input => {
          //Check if recipe, and if so, add another for loop here for adding all food associated with it, according to servings, to the list
          //Divide each amount and servings found by the amount of servings/ total servings of initial recipe (i.e. 1 serving of pizza dough is 0.25 * x amount of flour per serving) this can all be done in the background
          if (input.name && foodDBItems.some(item => item.title == input.name.replace("_servings", ""))) {
            ingredients.add(input.name);
          } else if(input.name) {
            recipes.add(input.name);
          }
        });
      }
      if (formPage3) {
        const inputs = formPage3.querySelectorAll("input, select, textarea");
        inputs.forEach(input => {
          if (input.name) {
            instructions.add(input.name);
          }
        });
      }
      // formData.forEach((value, key) => {
      //   console.log("Key: "+key+" Value: "+value);
      // });
      // return;

      formData.forEach((value, key) => {
        if (ingredients.has(key) && !key.includes('_servings')) {
          // console.log(`Field "${key}" came from #formPage2`);
          const servingsElem = document.getElementById(`${key}_servings`);
          jsonData.food.push({
            food_id: foodDBItems.find(item => item.title === key).id,
            amount: value,
            servings: servingsElem.value
          });
        } else if(instructions.has(key)) {
          // console.log(`Field "${key}" came from #formPage3`);
          jsonData.instruction.push({
            title: titleValue+" "+key,
            step: Number(key.match(/\d+/g)[0]),
            instruction: value
          });
        } else if(recipes.has(key)) {
          //In here, get recipe from recipeDBItems, then for the amount of servings out of the total servings, multiply the numbers for directions, then push to food
          // jsonData.recipe.push({
          //   key: value,
          // });
        } else {
          jsonData[key] = value;
        }
        // console.log(key + " " + value);
      });
      console.log(jsonData);
      return;

      const result = await pushDBItemNew("/add-recipe", jsonData);
      if (result) {
        cleanUpFormsComplete();
        showPage('recipeForm', 1);
        getRecipesFromDB();
      }
    }

    function updateHighlight(index, items) {
      items.forEach((item, i) => {
        item.classList.toggle('active', i === index);
        if (i === index) {
          item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
      });
    }

    /*
      This function takes in an array list of items that have a property "title" for each that displays them in a dropdown list.
      It needs to be updated to only require a parent div, and then find the elements which correspond to the elements already passed, which should be easy enough
    */
    function populateDropdown(referenceChild, jsonData, withExtras = false, required = false) {
      const formContainer = referenceChild.closest('.form-container');
      const dropdownMenu = referenceChild.querySelector('.dropdownMenu');
      const dropdownContainer = referenceChild.querySelector('.dropdownContainer');
      const dropdownBtn = referenceChild.querySelector('.dropdownBtn');
      const clearInputsButton = referenceChild.closest('.removeFields');
      const amountOfItems = jsonData.length;
      let currentIndex = { value: -1 };
      let listOfList = [];
      const keyHandler = createKeyDownHandler(currentIndex, listOfList);

      dropdownMenu.innerHTML = '';
      if (jsonData.length === 0) {
        dropdownMenu.innerHTML = '<li class="list-group-item text-muted">No results</li>';
        return;
      }

      jsonData.forEach((item, index) => {
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
          if(clearInputsButton) {
            if(clearInputsButton.style.display == 'inline-block' && inputIds.length == 0) clearInputsButton.style.display = "none";
          }
        });

        const verticalRule = document.createElement('div');
        verticalRule.className = 'vr';

        const input = document.createElement('input');
        input.type = 'text';
        input.id = item.title;//.toLowerCase().replaceAll(" ", "_");
        input.name = item.title;//.toLowerCase().replaceAll(" ", "_");
        input.required = false;
        input.className = 'form-control manual-scraped mr-2 w-auto ms-auto';
        input.style.minwidth = '200px';
        if (required) {
          input.required = true;
        }

        if(withExtras) {
          const label = document.createElement('a');
          // label.setAttribute('for', item.title);
          label.className = 'form-label me-2 mb-0 text-nowrap manual-scraped';
          label.style.minwidth = '140px';
          label.textContent = item.title;
          label.target = "_blank";
          label.href = item.html_link != null ? `${item.html_link}` : " ";
          label.tabIndex = -1;
          input.placeholder = "tsp/ Tbsp/ amount";

          let totalServings = item.servings != null ? `(${item.servings})` : "";
          let servingSize = item.serving_size != null ? `${item.serving_size}` : "1";
          let servingUnit = item.serving_unit != null ? `${item.serving_unit}` : "";

          const inputServings = document.createElement('input');
          inputServings.type = 'text';
          inputServings.className = 'form-control manual-scraped mr-2 w-auto ms-auto';
          inputServings.style.minwidth = '200px';
          inputServings.id = item.title+"_servings";
          inputServings.name = item.title+"_servings";
          inputServings.required = true;

          if (totalServings == "") {
            let servingsPerPound = 454/servingSize;
            servingsPerPound = parseFloat(servingsPerPound.toFixed(2));
            inputServings.placeholder = servingsPerPound+" servings per pound";
          } else {
            // input.value = totalServings.replace("(", "").replace(")", "");
            inputServings.placeholder = totalServings.replace("(", "").replace(")", "") + " / "+servingSize+" "+servingUnit;
          }

          const leftSide = document.createElement('div');
          leftSide.className = 'd-flex align-items-center gap-2';
          leftSide.append(span, verticalRule.cloneNode(true), label);

          const rightSide = document.createElement('div');
          rightSide.className = 'd-flex align-items-center gap-2 ms-auto';
          rightSide.append(input, verticalRule.cloneNode(true), inputServings);

          wrapper.style.display = 'flex';
          wrapper.style.justifyContent = 'space-between';
          wrapper.style.alignItems = 'center';
          wrapper.style.width = '100%';

          wrapper.append(leftSide, rightSide);
        } else {
          const label = document.createElement('label');
          label.setAttribute('for', item.title);
          label.className = 'form-label me-2 mb-0 text-nowrap manual-scraped';
          label.style.minwidth = '140px';
          label.textContent = item.title;

          const leftSide = document.createElement('div');
          leftSide.className = 'd-flex align-items-center gap-2';
          leftSide.append(span, verticalRule.cloneNode(true), label);

          const rightSide = document.createElement('div');
          rightSide.className = 'd-flex align-items-center gap-2 ms-auto';
          rightSide.append(input);

          wrapper.style.display = 'flex';
          wrapper.style.justifyContent = 'space-between';
          wrapper.style.alignItems = 'center';
          wrapper.style.width = '100%';

          wrapper.append(leftSide, rightSide);
        }

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
          if(clearInputsButton){
            if(clearInputsButton.style.display == 'none' && inputIds.length > 0) clearInputsButton.style.display = "inline-block";
          }
          dropdownBtn.focus();
        });

        listOfList.push(li);
        dropdownMenu.appendChild(li);
      });
      
      dropdownContainer.onkeydown = keyHandler;
    }

    function createKeyDownHandler(currentIndexRef, listOfList) {
      return function handleKeyDown(e) {
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          currentIndexRef.value = (currentIndexRef.value + 1) % listOfList.length;
          updateHighlight(currentIndexRef.value, listOfList);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          currentIndexRef.value = (currentIndexRef.value - 1 + listOfList.length) % listOfList.length;
          updateHighlight(currentIndexRef.value, listOfList);
        } else if (e.key === ' ' || e.key === 'Enter') {
          e.preventDefault();
          listOfList.forEach(item => item.classList.remove('active'));
          if (currentIndexRef.value >= 0) {
            const selectedItem = listOfList[currentIndexRef.value];
            selectedItem.click();
          }
        }
      };
    }

    //This function closes all forms on the page, and I think keeps their value since it jsut sets display to none, and then opens the form specified by the input
    function openForm(formID) {
      const allForms = document.querySelectorAll('.popup');
      allForms.forEach(element => {
        element.style.display = 'none';
      })
      if(!showPage(formID, 1)) {
        const popup = document.getElementById(formID);
        if (popup) popup.style.display = "block";
        const focusables = popup.querySelectorAll('input, textarea');

        for (const el of focusables) {
          const style = window.getComputedStyle(el);
          const isVisible = style.display !== 'none' && style.visibility !== 'hidden' && el.offsetParent !== null;

          if (isVisible) {
            el.focus();
            break; // ✅ Focus only the first visible one
          }
        }
      }
    }

    //Closes the specific form for the input formID
    function closeForm(formID) {
      const popup = document.getElementById(formID);
      if (popup) popup.style.display = "none";
      showPage(formID, 1); // Reset to Page 1
    }

    //When a form has multiple pages, input the formID and the page you want to go to. At the moment only assumes 2 pages
    const labelSets = {
      recipeForm: {
        1: "Add New Recipe",
        2: "Add Ingredient List",
        3: "Add Instructions"
      },
      recipeDisplay: {
        1: "Ingredient List",
        2: "Instruction List",
      },
      foodForm: {
        1: "Food Name:"
      },
      fridgeForm: {
        1: "Add Food to Fridge"
      }
      // Add more formID-specific label sets here
    };

    function showPage(formID, pageNumber) {
      const form = document.getElementById(formID);
      const labelText = form?.querySelector('#form-label-text');

      if (!form || !labelText) {
        console.warn('Form or labelText not found');
        return false;
      }

      let pageFound = false;

      Array.from(form.querySelectorAll('.form-page')).forEach(child => {
        if (child.id?.startsWith('formPage')) {
          const pageNum = parseInt(child.id.replace('formPage', ''), 10);

          if (pageNum === pageNumber) {
            child.style.display = 'block';
            const focusTarget = child.querySelector('input, textarea, button');
            if (focusTarget) focusTarget.focus();
            pageFound = true;
          } else {
            child.style.display = 'none';
          }
        }
      });

      const pageLabels = labelSets[formID] || {};
      labelText.innerHTML = pageLabels[pageNumber] ||  ` `;

      if (!pageFound) {
        console.warn(`Page ${pageNumber} not found in form ${formID}`);
        return false;
      }
    }

    /*
      Somewhat specific function for rendering onto the nutritionForm for each value obtained when webscraping from websites for nutrition information.
      Possibly change in the future to be more flexible for other websites, as server side code can sort through data also.
    */
    function renderNutrition(inputArray) {
      if (!Array.isArray(inputArray)) {
        console.warn("Unexpected response format");
        return;
      }

      const parentDiv = document.getElementById('foodForm');
      const formContainer = parentDiv.querySelector('.form-container');
      const referenceChild = formContainer.querySelector('.form-divider');
      const clearInputsButton = parentDiv.querySelector('.removeFields')

      foodLinkInput.value = inputArray[0]['product'];
      clearInputsButton.style.display = 'inline-block';

      inputArray.filter(item => item.nutrient).reverse().forEach(item => {
        let numeric = '';
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-3 d-flex align-items-center flex-nowrap auto-scraped';
        wrapper.style.maxWidth = '100%';
        if (item.nutrient === "Total Servings") {
          item.nutrient = "Servings";
        }

        // console.log(item);

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

    //Updates a global variable with all the food items in the database
    function getFoodFromDB() {
      foodDBItems = [];
      fetch('/get-food')
        .then(response => response.json())
        .then(data => {
          data.foodItems.forEach( food => {
            foodDBItems.push(food);
          });
          // console.log(foodDBItems);
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    }

    //Updates a global variable with all the recipe items in the database, and the relations to them
    async function getRecipesFromDB() {
      recipeDBItems = [];

      fetch('/get-recipe')
        .then(response => response.json())
        .then(data => {
          data.recipeJson.forEach( recipe => {
            recipeDBItems.push(recipe);
          });
          // console.log(recipeDBItems);
          populateRecipes();
        })
        .catch(error => {
          console.error('Error fetching data:', error);
        });
    }

    function editRecipe() {
      const parentEl = document.getElementById('recipeDisplay');
      // console.log(parentEl);
      const recipeTitle = parentEl.querySelector("#dishName").innerHTML;
      const recipeServings = parentEl.querySelector('#dishServings').innerHTML.toString().replaceAll(" servings", "");
      const recipeDescription = parentEl.querySelector('#dishDescription p').innerHTML;

      const recipeFormTitle = document.querySelector('#recipeForm #title');
      const recipeFormServings = document.querySelector('#recipeForm #servings');
      const recipeFormDescription = document.querySelector('#recipeForm #description');
      const dropdownContainer = document.querySelector('#recipeForm #dropdownContainer');
      const referenceChild = document.getElementById('recipeForm').querySelector('.dropdown');
      const formPage2 = document.getElementById('recipeForm').querySelector('#formPage2');
      const formPage3 = document.getElementById('recipeForm').querySelector('#formPage3');

      const removeFieldsComplete = document.getElementById('removeFieldsComplete');
      const removeFields = document.getElementById('recipeForm').querySelector('#removeFields');
      cleanUpFormsComplete();
      removeFieldsComplete.style.display = "block";
      removeFields.style.display = "block";

      openForm('recipeForm');
      recipeFormTitle.value = recipeTitle;
      recipeFormServings.value = recipeServings;
      recipeFormDescription.value = recipeDescription;

      ingredientList.slice().reverse().forEach(ingredient => {
        const item = foodDBItems.find(food => food.title === ingredient.title);
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
          if(clearInputsButton) {
            if(clearInputsButton.style.display == 'inline-block' && inputIds.length == 0) clearInputsButton.style.display = "none";
          }
        });

        const verticalRule = document.createElement('div');
        verticalRule.className = 'vr';

        const label = document.createElement('a');
        label.className = 'form-label me-2 mb-0 text-nowrap manual-scraped';
        label.style.minwidth = '140px';
        label.textContent = item.title;
        label.target = "_blank";
        label.href = item.html_link != null ? `${item.html_link}` : " ";
        label.tabIndex = -1;

        const input = document.createElement('input');
        input.type = 'text';
        input.id = item.title;
        input.name = item.title;
        input.required = true;
        input.className = 'form-control manual-scraped mr-2 w-auto ms-auto';
        input.style.minwidth = '200px';
        input.placeholder = "tsp/ Tbsp/ amount";
        input.value = ingredient.amount;

        let totalServings = item.total_servings != null ? `(${item.total_servings})` : "";
        let servingSize = item.serving_size != null ? `${item.serving_size}` : "";
        let servingUnit = item.serving_unit != null ? `${item.serving_unit}` : "";

        const inputServings = document.createElement('input');
        inputServings.type = 'text';
        inputServings.className = 'form-control manual-scraped mr-2 w-auto ms-auto';
        inputServings.style.minwidth = '200px';
        inputServings.id = item.title+"_servings";
        inputServings.name = item.title+"_servings";
        inputServings.required = true;
        inputServings.value = ingredient.servings ?? '';

        if (totalServings == "") {
          let servingsPerPound = 454/servingSize;
          servingsPerPound = parseFloat(servingsPerPound.toFixed(2));
          inputServings.placeholder = servingsPerPound+" servings per pound";
        } else {
          // input.value = totalServings.replace("(", "").replace(")", "");
          inputServings.placeholder = totalServings.replace("(", "").replace(")", "") + " servings per container";
        }

        const leftSide = document.createElement('div');
        leftSide.className = 'd-flex align-items-center gap-2';
        leftSide.append(span, verticalRule.cloneNode(true), label);

        const rightSide = document.createElement('div');
        rightSide.className = 'd-flex align-items-center gap-2 ms-auto';
        rightSide.append(input, verticalRule.cloneNode(true), inputServings);

        wrapper.style.display = 'flex';
        wrapper.style.justifyContent = 'space-between';
        wrapper.style.alignItems = 'center';
        wrapper.style.width = '100%';

        wrapper.append(leftSide, rightSide);
        if (referenceChild && referenceChild.nextSibling) {
          formPage2.insertBefore(wrapper, referenceChild.nextSibling);
        } else {
          // If there's no next sibling, just append at the end
          formPage2.appendChild(wrapper);
        }
      });

      const instructionEl = formPage3.querySelector('#instruction-list');
      // console.log(instructionList);
      document.querySelectorAll('.step').forEach(el => {
        el.remove();
      });
      instructionList.forEach(item => {
        if(item.step == 1) {
          formPage3.querySelector("#step-1").value = item.instruction;
        } else {
          const wrapper = document.createElement('div');
          wrapper.className = 'mb-3 d-flex align-items-center flex-nowrap instruction added-step step'+item.step;
          wrapper.id = 'step'+item.step;
          wrapper.style.maxWidth = '100%';

          const label = document.createElement('label');
          label.className = 'form-label me-2 mb-0 text-nowrap justify-content-center'
          label.target = 'step-'+item.step;
          label.style.width = '140px';
          label.innerHTML = 'Step '+item.step;

          const textArea = document.createElement('textarea');
          textArea.className = 'form-control'
          textArea.name = 'step-'+item.step;
          textArea.id = 'step-'+item.step;
          textArea.rows = '3';
          textArea.placeholder = "Add in your instruction here for step "+item.step+"...";
          textArea.value = item.instruction;

          const span = document.createElement('span');
          span.className = 'text-danger manual-scraped fs-2 text-center mb-2';
          span.style.cursor = 'pointer';
          span.style.fontWeight = 'bold';
          span.innerHTML = '&times;';
          span.addEventListener('click', () => {
            wrapper.remove();
          });

          wrapper.append(label, textArea, span);

          instructionEl.insertBefore(wrapper, formPage3.querySelector('#next-step-button'));
        }
      });
    }

    function populateDisplay(displayID, jsonData) {
      // console.log(jsonData);
      const parentDiv = document.getElementById(displayID);
      const dishName = parentDiv.querySelector('#dishName');
      const dishServings = parentDiv.querySelector('#dishServings');
      const dishDescription = parentDiv.querySelector('#dishDescription');
      const dishIngredients = parentDiv.querySelector('#dishIngredients');
      const dishInstructions = parentDiv.querySelector('#dishInstructions');
      const spacerDiv = document.createElement('div');
      spacerDiv.style.flex = "0 0 10%";

      // <h3 id="dishName" class="form-label me-2 mb-0 text-nowrap">Name Of Dish</h3>
      //  <div style="flex: 0 0 10%;"></div>
      //<h4 id="dishServings" class="form-label me-2 mb-0 text-nowrap">Servings</h4>

      dishName.innerHTML = jsonData.title;
      dishServings.innerHTML = jsonData.servings+" servings";

      const descriptionText = document.createElement('p');
      descriptionText.className = "form-label me-2 mb-0 text-wrap"
      descriptionText.innerHTML = jsonData.description;

      dishDescription.innerHTML = '';
      dishDescription.appendChild(descriptionText);

      dishIngredients.innerHTML = '';
      jsonData.food.sort((a, b) => {
        return a.title.localeCompare(b.title);
      });
      ingredientList = [];
      jsonData.food.forEach(item => {
        ingredientList.push(item);
        const row = document.createElement('div');
        row.className = "mb-3 d-flex justify-content-between align-items-center mb-2 w-100 flex-wrap food-recipe-item";
        row.style.width = "100%";

        const foodItem = document.createElement('h5');
        foodItem.className = "form-label me-2 mb-0 text-wrap"
        foodItem.innerHTML = item.title;

        const foodAmount = document.createElement('p');
        foodAmount.className = "form-label me-2 mb-0 text-wrap"
        foodAmount.innerHTML = item.amount;

        row.appendChild(foodItem);
        row.appendChild(spacerDiv.cloneNode());
        row.appendChild(foodAmount);

        dishIngredients.appendChild(row);
      });

      dishInstructions.innerHTML = '';
      jsonData.instruction.sort((a, b) => {
        return a.step - b.step;
      });
      instructionList = [];
      jsonData.instruction.forEach(item => {
        instructionList.push(item);
        const row = document.createElement('div');
        row.className = "mb-3 d-flex align-items-left mb-2 w-100 food-ingredient-item";
        row.style.width = "100%";

        const instructionStep = document.createElement('h5');
        instructionStep.className = "form-label me-2 mb-0 text-nowrap"
        instructionStep.innerHTML = "Step "+item.step+": ";

        const actualInstruction = document.createElement('p');
        actualInstruction.className = "form-label me-2 mb-0 text-wrap"
        actualInstruction.innerHTML = item.instruction;

        row.appendChild(instructionStep);
        row.appendChild(actualInstruction);

        dishInstructions.appendChild(row);
      });
    }

    function openRecipe() {
      openForm('recipeDisplay');
      populateDisplay('recipeDisplay', this);
    }

    /*
      <div class="col">
        <div class="card p-0" style="width: 100%;">
          <!-- Header -->
          <div class="bg-primary text-white text-center py-4 rounded-top">
            <h3 class="m-0">Recipes</h3>
          </div>
          <!-- Grey content area -->
          <div class="bg-light rounded-bottom" style="height: 250px;"></div>
        </div>
      </div>
    */
    function populateRecipes() {
      const grid = document.getElementById('grid-container');
      grid.querySelectorAll('.col').forEach( el => {
        el.remove();
      });
      recipeDBItems.forEach(recipe => {
        const wrapper = document.createElement('div');
        wrapper.className = "col h-100";
        wrapper.style.cursor = "pointer";
        wrapper.onclick = openRecipe.bind(recipe);

        const card = document.createElement('div');
        card.className = "p-1 card-flex w-100";

        const cardHeader = document.createElement('div');
        cardHeader.className = "bg-info text-white text-center py-4 rounded-top";

        const cardHeaderText = document.createElement('h3');
        cardHeaderText.className = "m-0 text-white";
        cardHeaderText.innerHTML = recipe.title;

        const cardBody = document.createElement('div');
        cardBody.className = "bg-light rounded-bottom card-body-flex p-3";

        let recipeTotalCalories = 0;
        let recipeTotalFat = 0;
        let recipeSaturatedFat = 0;
        let recipeCholesterol = 0;
        let recipeSodium = 0;
        let recipeTotalCarbohydrates = 0;
        let recipeDietaryFiber = 0;
        let recipeSugars = 0;
        let recipeProtein = 0;

        //Add in ul of: servings, major macros, description

        // const instructionList = document.createElement('ul');
        // instructionList.className = "list-group list-group-item"; //list-group-numbered";

        recipe.food.forEach(item => {
          // console.log(item);
          const foodItem = foodDBItems.find(foodItem => foodItem.title === item.title);
          // console.log(foodItem);
          const foodTotalCalories = foodItem.Calories;
          const foodTotalFat = foodItem.Total_Fat;
          const foodSaturatedFat = foodItem.Saturated_Fat;
          const foodCholesterol = foodItem.Cholesterol;
          const foodSodium = foodItem.Sodium;
          const foodTotalCarbohydrates = foodItem.Total_Carbohydrate;
          const foodDietaryFiber = foodItem.Dietary_Fiber;
          const foodSugars = foodItem.Sugars;
          const foodProtein = foodItem.Protein;

          recipeTotalCalories += foodTotalCalories != null ? Number(foodTotalCalories) * item.servings : 0;
          recipeTotalFat += foodTotalFat != null ? Number(foodTotalFat) * item.servings : 0;
          recipeSaturatedFat += foodSaturatedFat != null ? Number(foodSaturatedFat) * item.servings : 0;
          recipeCholesterol += foodCholesterol != null ? Number(foodCholesterol) * item.servings : 0;
          recipeSodium += foodSodium != null ? Number(foodSodium) * item.servings : 0;
          recipeTotalCarbohydrates += foodTotalCarbohydrates != null ? Number(foodTotalCarbohydrates) * item.servings : 0;
          recipeDietaryFiber += foodDietaryFiber != null ? Number(foodDietaryFiber) * item.servings : 0;
          recipeSugars += foodSugars != null ? Number(foodSugars) * item.servings : 0;
          recipeProtein += foodProtein != null ? Number(foodProtein) * item.servings : 0;

          // console.log(foodItem);
          // console.log("foodTotalCalories: "+foodTotalCalories);
          // console.log("foodTotalFat: "+foodTotalFat);
          // console.log("foodSaturatedFat: "+foodSaturatedFat);
          // console.log("foodCholesterol: "+foodCholesterol);
          // console.log("foodSodium: "+foodSodium);
          // console.log("foodTotalCarbohydrates: "+foodTotalCarbohydrates);
          // console.log("foodDietaryFiber: "+foodDietaryFiber);
          // console.log("foodSugars: "+foodSugars);
          // console.log("foodProtein: "+foodProtein);
        });

        const servings = {
          Calories:      (recipeTotalCalories      / recipe.servings).toFixed(2),
          Total_Fat:           (recipeTotalFat           / recipe.servings).toFixed(2),
          Saturated_Fat:    (recipeSaturatedFat       / recipe.servings).toFixed(2),
          Cholesterol:   (recipeCholesterol        / recipe.servings).toFixed(2),
          Sodium:        (recipeSodium             / recipe.servings).toFixed(2),
          Total_Carbohydrates:         (recipeTotalCarbohydrates / recipe.servings).toFixed(2),
          Dietary_Fiber:         (recipeDietaryFiber       / recipe.servings).toFixed(2),
          Sugars:        (recipeSugars             / recipe.servings).toFixed(2),
          Protein:       (recipeProtein            / recipe.servings).toFixed(2)
        }

        const recipeNutrition = document.createElement('div');

        let row = document.createElement('div');
        row.className = 'd-flex justify-content-between align-items-center mb-2';
        
        // left = the numeric value
        let left = document.createElement('span');
        left.textContent = "Per Serving";
        
        // right = the text label
        let right = document.createElement('span');
        right.textContent = "% DV";
        
        row.append(left, right);
        recipeNutrition.append(row);
        // <hr class="hr-blurry my-3 form-divider">
        let blurryHR = document.createElement('hr');
        blurryHR.className = "hr-blurry my-3 form-divider";
        recipeNutrition.append(blurryHR);

        Object.entries(servings).forEach(([label, value]) => {
          row = document.createElement('div');
          row.className = 'd-flex justify-content-between align-items-center mb-2';
          
          // left = the numeric value
          left = document.createElement('span');
          left.textContent = value+" - "+(maxNutritionValues[label]?.unit ?? " ")+" "+label.replace("_", " ");
          
          // right = the text label
          right = document.createElement('span');
          amt = maxNutritionValues[label]?.amount;
          right.textContent = amt != null ? ((value/maxNutritionValues[label]?.amount)*100).toFixed(2)+"%" : ((value/2500)*100).toFixed(2)+"%";
          
          row.append(left, right);
          recipeNutrition.append(row);
        });


        // Assemble the card
        card.appendChild(cardHeader);
        card.appendChild(cardBody);
        cardHeader.appendChild(cardHeaderText);
        cardBody.appendChild(recipeNutrition);

        // Add card to wrapper
        wrapper.appendChild(card);

        //Add wrapper to document in right area
        grid.appendChild(wrapper);
      });
    }

    //Removes or clears all fields from forms that use the below classes for inputs. Called only when something succeeds.
    function cleanUpForms() {
      document.querySelectorAll('.auto-scraped').forEach(element => {
        element.remove();
      });
      document.querySelectorAll('input, textarea').forEach(element => {
        element.value = '';
      });
      document.querySelectorAll('#removeFields, .removeFields').forEach(el => {
        el.style.display = "none";
      });
      foodLinkInput.value = "";
    }

    //Removes all fields from forms that use the below classes for inputs. Called only when something succeeds.
    function cleanUpFormsComplete() {
      document.querySelectorAll('.auto-scraped').forEach(element => {
        element.remove();
      });
      document.querySelectorAll('.manual-scraped').forEach(element => {
        element.remove();
      });
      document.querySelectorAll('.removeFields').forEach(el => {
        el.style.display = "none";
      });
      document.querySelectorAll('#removeFieldsComplete').forEach(el => {
        el.style.display = "none";
      });
      document.querySelectorAll('input, textarea').forEach(el => {
        el.value = ''
      });
      document.querySelectorAll('.added-step').forEach(el => {
        el.remove();
      });
      document.querySelectorAll('#removeFields, .removeFields').forEach(el => {
        el.style.display = "none";
      });
      foodLinkInput.value = "";
    }

    function showAlertBox(alertText) {
      let result;
      const items = Array.isArray(alertText) ? alertText : [alertText];

      // Remove any existing <p> tags but keep other content
      const oldParagraphs = alertEl.querySelectorAll('p');
      oldParagraphs.forEach(p => p.remove());

      // Append each message as a <p> tag
      items.forEach(message => {
        const p = document.createElement("p");
        p.textContent = message;
        message.toLowerCase().includes('success') ? p.className = 'text-dark' : 'text-danger';
        alertEl.appendChild(p);
      });

      // Combine all messages into one string for switch logic
      const fullText = items.join("\n");

      // Determine alert type based on message content
      switch (true) {
        case fullText.toLowerCase().includes("success"):
          alertEl.classList.add("alert-info");
          alertEl.classList.remove("alert-danger");
          result = true;
          break;
        default:
          alertEl.classList.remove("alert-info");
          alertEl.classList.add("alert-danger");
          result = false;
      }

      // Show the alert box
      alertEl.style.display = "block";
      alertEl.classList.add("show");

      // Auto-close after 5 seconds
      setTimeout(() => {
        alertEl.classList.remove("show");
        alertEl.classList.add("hide");

        setTimeout(() => {
          alertEl.style.display = "none";
          alertEl.classList.remove("hide");

          // ✅ Remove only <p> tags, leave other content intact
          const paragraphs = alertEl.querySelectorAll('p');
          paragraphs.forEach(p => p.remove());
        }, 500); // Delay to finish transition
      }, 5000);

      return result;
    }

    //Shows a confirmation box to have the user confirm or cancel the choice they are making.
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

    /*
      If it is a valid URL, and failcase is true, the function fails
      If it is not a valid URL and failcase is true, the function passes
      If it is a valid URL and failcase is false, the function passes
      If it is not a valid URL and failcase is false, the function fails
    */
    function checkURL(inputURL, failCase) {
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

    //Push a new item to the database
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
        success = showAlertBox(data.response.resultMessage);
        // console.log(data.response);
        return success; // Only return true after successful completion
      } catch (error) {
        console.error("Error:", error);
        return success; // Return false if something goes wrong
      }
    }
  </script>
</body>
</html>