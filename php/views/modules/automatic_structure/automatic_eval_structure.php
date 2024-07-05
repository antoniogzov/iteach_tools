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

    <!-- Hero Section -->
    <section id="hero" class="hero section">
        <div class="hero-bg">
            <img src="assets/img/hero-bg-light.webp" alt="">
        </div>
        <div class="container text-center">
            <div class="d-flex flex-column justify-content-center align-items-center">
                <h1 data-aos="fade-up">Gestión de <span>Estructura de evaluación</span></h1>
            </div>
        </div>

    </section><!-- /Hero Section -->

    <!-- Featured Services Section -->
    <section id="featured-services" class="featured-services section light-background">

        <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="slct-lvl-combination">Seleccione un level combination</label>
                        <select class="form-select" id="slct-lvl-combination">
                            <option selected disabled>Seleccione una opción</option>
                            <?php foreach ($getLevelCombinations as $levelCombination) : ?>
                                <?php if ($id_level_combination == $levelCombination->id_level_combination) : ?>
                                    <option selected value="<?= $levelCombination->id_level_combination ?>"><?= $levelCombination->combination_name ?></option>
                                <?php else : ?>
                                    <option value="<?= $levelCombination->id_level_combination ?>"><?= $levelCombination->combination_name ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="slct-period">Seleccione un periodo</label>
                        <select class="form-select" aria-label="Default select example" id="slct-period">
                            <option selected>Seleccione un periodo</option>
                            <?php if (!empty($getPeriods)) : ?>
                                <?php foreach ($getPeriods as $period) : ?>
                                    <?php if ($id_period_calendar == $period->id_period_calendar) : ?>
                                        <option selected value="<?= $period->id_period_calendar ?>"><?= $period->no_period ?></option>
                                    <?php else : ?>
                                        <option value="<?= $period->id_period_calendar ?>"><?= $period->no_period ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <br>
            <div class="container">
                <h1>Asignaturas sin Estructura de evaluación</h1>
                <br>
                <?php
                $assignments = $automatic_structure_model->getAssignmentsAutomatic();
                $ass_count = 0;
                ?>
                <div class="table-responsive">
                    <?php foreach($assignments as $assignment):?>
                        <h1>ASIGNATURA: <?=$assignment->name_subject?> | <?=$assignment->group_code?> | <?=$assignment->id_assignment?></h1>
                        <?php endforeach; ?>
                </div>

            </div>
    </section><!-- /Featured Services Section -->

</main>
<script src="js/updateStructure.js"></script>