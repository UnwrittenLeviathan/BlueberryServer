CREATE TABLE `recipe` (
  `id` integer PRIMARY KEY,
  `name` varchar(255),
  `created_at` timestamp
);

CREATE TABLE `fridge` (
  `id` integer PRIMARY KEY,
  `name` varchar(255),
  `created_at` timestamp
);

CREATE TABLE `food` (
  `id` integer PRIMARY KEY,
  `title` varchar(255),
  `serving_size` integer,
  `serving_unit` varchar(255),
  `Calories` integer COMMENT "None   2500",
  `Total_Fat` integer COMMENT "gram   80",
  `Saturated_Fat` integer COMMENT "gram   20",
  `Cholesterol` integer COMMENT "milligram  300",
  `Sodium` integer COMMENT "milligram 2300",
  `Total_Carbohydrates` integer COMMENT "gram   275",
  `Dietary_Fiber` integer COMMENT "gram   28",
  `Sugars` integer COMMENT "gram    50",
  `Protein` integer COMMENT "gram   50",
  `Vitamin_D` integer COMMENT "microgram  20",
  `Calcium` integer COMMENT "milligram  1300",
  `Iron` integer COMMENT "milligram 18",
  `Potassium` integer COMMENT "milligram  4700",
  `Vitamin_A` integer COMMENT "microgram  900",
  `Vitamin_C` integer COMMENT "milligram  90",
  `Vitamin_E` integer COMMENT "milligram  15",
  `Vitamin_K` integer COMMENT "microgram  120",
  `Thiamin` integer COMMENT "milligram  1.2",
  `Riboflavin` integer COMMENT "milligram 1.3",
  `Niacin` integer COMMENT "milligram 16",
  `Vitamin_B6` integer COMMENT "milligram 1.7",
  `Folate_Folic_Acid` integer COMMENT "microgram  400",
  `Vitamin_B12` integer COMMENT "microgram  2.4",
  `Biotin` integer COMMENT "microgram 30",
  `Phosphorus` integer COMMENT "milligram 1250",
  `Iodine` integer COMMENT "microgram 150",
  `Magnesium` integer COMMENT "milligram  420",
  `Zinc` integer COMMENT "milligram 11",
  `Copper` integer COMMENT "milligram 0.9",
  `Manganese` integer COMMENT "milligram  2.3",
  `Chloride` integer COMMENT "milligram 2300",
  `Chromium` integer COMMENT "microgram 35",
  `Molybdenum` integer COMMENT "microgram 45",
  `Choline` integer COMMENT "milligram  550",
  `Pantothenic_Acid` integer COMMENT "milligram 5",
  `Selenium` integer COMMENT "microgram 55",
  `edited_at` timestamp,
  `created_at` timestamp
);

CREATE TABLE `food_recipe` (
  `id` integer PRIMARY KEY,
  `recipe_id` integer NOT NULL,
  `food_id` integer NOT NULL,
  `fridge_id` integer DEFAULT -1,
  `amount` integer DEFAULT 0,
  `created_at` timestamp
);

CREATE TABLE `instruction_recipe` (
  `id` integer PRIMARY KEY,
  `recipe_id` integer,
  `instruction_id` integer,
  `created_at` timestamp
);

CREATE TABLE `instruction` (
  `id` integer PRIMARY KEY,
  `step` integer,
  `instruction` varchar(255),
  `created_at` timestamp
);

ALTER TABLE `food_recipe` ADD FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`);

ALTER TABLE `food_recipe` ADD FOREIGN KEY (`food_id`) REFERENCES `food` (`id`);

ALTER TABLE `food_recipe` ADD FOREIGN KEY (`fridge_id`) REFERENCES `fridge` (`id`);

ALTER TABLE `instruction_recipe` ADD FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`id`);

ALTER TABLE `instruction_recipe` ADD FOREIGN KEY (`instruction_id`) REFERENCES `instruction` (`id`);
