<?php
$getLevelCombinations = $automatic_structure_model->getLevelCombinations();
$getPeriods = array();
if (isset($_GET['id_level_combination']) && isset($_GET['id_period_calendar'])) {
    $id_level_combination = $_GET['id_level_combination'];
    $id_period_calendar = $_GET['id_period_calendar'];

    $getPeriods = $automatic_structure_model->getPeriods($id_level_combination);
}
?>
<main class="main">



    <!-- Featured Services Section -->
    <section id="featured-services" class="featured-services section light-background">


        <br>
        <br>
        <div class="container">
            <h1>Asignaturas sin Estructura de evaluación</h1>
            <br>

            <div class="table-responsive">
                <?php
                $assignments = $automatic_structure_model->getAssignmentsAutomatic();
                $ass_count = 0;
                ?>
                <?php foreach ($assignments as $assignment) : ?>

                <?php endforeach; ?>
            </div>

        </div>
    </section><!-- /Featured Services Section -->

</main>
<script src="js/updateStructure.js"></script>