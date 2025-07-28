<nav class="navbar navbar-expand-sm bg-primary mb-4 pb-3">
	<div class="container-fluid">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link" href="/">Home</a>
			</li>
			<div class="vr"></div>
			<li class="nav-item">
				<a class="nav-link" href="/recipes">Recipes</a>
			</li>
		</ul>
		<!-- Centered page title -->
		<div
	      class="position-absolute top-50 start-50 translate-middle text-center w-25"
	     >
	      <h1
	        id="pageTitle"
	        class="text-white fw-bold mb-2"
	        style="font-size: 2.25rem;"
	      >
	        Default Title
	      </h1>
	      <hr class="border-light border-2 opacity-100 w-50 mx-auto m-0" />
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
