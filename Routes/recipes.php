<!DOCTYPE html>
<html>
<head>
	<?php include 'head.php';?>
</head>
<body>
	<?php include 'body-header.php'; ?>
	<div class="my-4" style="width: 90%; margin: 0 auto;">
    <div class="row" id="itemsRow">
      <!-- JS will inject columns + buttons here -->
    </div>
  </div>

  <script>
    // Your list of items
    const items = [
      'Item 1', 'Item 2', 'Item 3', 'Item 4', 'Item 5',
      'Item 6', 'Item 7', 'Item 8', 'Item 9', 'Item 10',
      'Item 11', 'Item 12', 'Item 13', 'Item 14', 'Item 15',
      'Item 16', 'Item 17', 'Item 18', 'Item 19', 'Item 20',
      'Item 21', 'Item 22'
    ];

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
  </script>

  <!-- Bootstrap 5 JS bundle (optional) -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>

	<!-- <div class="container d-flex">
		<div class="row">
			<div class="col d-flex">
				<button type="button" class="align-text-center d-flex btn btn-info btn-lg" onclick="addRecipe()">
					+
					<div class="vr"></div>
					Add New Recipe1
				</button>
			</div>
			<div class="col">
				<button type="button" class="d-flex btn btn-info btn-lg" onclick="addRecipe()">
					+
					<div class="vr"></div>
					Add New Recipe2
				</button>
			</div>
			<div class="col">
				<button type="button" class="d-flex btn btn-info btn-lg" onclick="addRecipe()">
					+
					<div class="vr"></div>
					Add New Recipe3
				</button>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<button type="button" class="d-flex btn btn-info btn-lg" onclick="addRecipe()">
					+
					<div class="vr"></div>
					Add New Recipe4
				</button>
			</div>
			<div class="col">
				<button type="button" class="d-flex btn btn-info btn-lg" onclick="addRecipe()">
					+
					<div class="vr"></div>
					Add New Recipe5
				</button>
			</div>
			<div class="col">
				<button type="button" class="d-flex btn btn-info btn-lg" onclick="addRecipe()">
					+
					<div class="vr"></div>
					Add New Recipe6
				</button>
			</div>
		</div></div> -->
</body>
</html>