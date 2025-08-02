<nav class="navbar navbar-expand-md bg-primary border-bottom mb-3 position-relative">
  <div class="container-fluid">

    <!-- collapse button (left on mobile) -->
    <button class="navbar-toggler ms-2 me-2 my-2" type="button" data-bs-toggle="collapse" data-bs-target="#navButtons" aria-controls="navButtons" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- left-aligned nav buttons -->
    <div class="collapse navbar-collapse" id="navButtons">
      <ul class="navbar-nav flex-row">
        <li class="nav-item p-0 me-2">
          <button class="btn nav-btn text-bold d-flex justify-content-center align-items-center" 
                  onclick="location.href='/'" style="height: 40px; width: auto;">
            Home
          </button>
        </li>
        <li class="nav-item p-0 me-2">
          <button class="btn nav-btn text-secondary d-flex justify-content-center align-items-center" 
                  onclick="location.href='/recipes'" style="height: 40px; width: auto;">
            Recipes
          </button>
        </li>
      </ul>
    </div>

    <!-- centered title -->
    <div class="navbar-title">
      <h1 id="pageTitle" class="text-white fw-bold mb-0 pt-1" style="font-size:2rem;">
        Default Title
      </h1>
      <div class="bg-white mx-auto mt-2" style="height: 1px; width: 20%;"></div>
    </div>

  </div>
</nav>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Map URLs to headings
  const titles = {
    '/':           'Camera',
    '/recipes':    'Recipes',
    // add more routes here…
  };

  // Pick the right title or fall back to <title>
  const current = titles[location.pathname] || document.title;
  document.getElementById('pageTitle').textContent = current;
</script>
