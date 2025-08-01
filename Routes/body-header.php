<nav class="navbar navbar-expand-md border-bottom bg-primary mb-3">
  <div class="container-fluid">

    <!-- Collapse button (visible on sm/md) -->
    <button
      class="navbar-toggler me-3"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#navButtons"
      aria-controls="navButtons"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Left-side buttons -->
    <div class="collapse navbar-collapse" id="navButtons">
      <div class="navbar-nav">
        <a class="nav-link btn btn-outline-info me-2 bg-light" href="/">Home</a>
        <a class="nav-link btn btn-outline-secondary me-2 bg-light" href="/recipes">Recipes</a>
        <a class="nav-link btn btn-outline-success bg-light" href="#">Button 3</a>
      </div>

      <div
          class="position-absolute top-50 start-50 translate-middle text-center w-25"
         >
          <h1
            id="pageTitle"
            class="text-white fw-bold mb-2 pt-2"
            style="font-size: 2.25rem;"
          >
            Default Title
          </h1>
          <hr class="border-light border-2 opacity-100 w-50 mx-auto m-0 pt-3" />
        </div>
    </div>
    </div>

  </div>
</nav>
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
