<?php
$getLevelCombinations = $structure_model->getLevelCombinations();
$getPeriods = array();
if (isset($_GET['id_level_combination']) && isset($_GET['id_period_calendar'])) {
    $id_level_combination = $_GET['id_level_combination'];
    $id_period_calendar = $_GET['id_period_calendar'];

    $getPeriods = $structure_model->getPeriods($id_level_combination);
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
        <?php if (isset($_GET['id_level_combination']) && isset($_GET['id_period_calendar']) && isset($_GET['id_period_calendar'])) : ?>
            <div class="container">
                <h1>Asignaturas sin Estructura de evaluación</h1>
                <br>
                <?php
                $assignments = $structure_model->getAssignments($id_level_combination, $id_period_calendar);
                $ass_count = 0;
                ?>
                <div class="table-responsive">
                    <button class="btn btn-lg btn-primary"><i class="fas fa-sync"></i> Actualizar todas las estructuras pendientes </button>
                    <br>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">ID</th>
                                <th scope="col">ID PERIOD</th>
                                <th scope="col">Materia</th>
                                <th scope="col">Grupo</th>
                                <th scope="col">Nivel Académico</th>
                                <th scope="col">Profesor</th>
                                <th scope="col">Sincronizar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($assignments)) :?>
                            <?php foreach ($assignments as $assignment) :
                                $ass_count++;
                            ?>
                                <tr>
                                    <th scope="row"><?= $ass_count ?></th>
                                    <th scope="row"><?= $assignment->id_assignment ?></th>
                                    <td><?= $assignment->id_period_calendar ?></td>
                                    <td><?= $assignment->name_subject ?></td>
                                    <td><?= $assignment->group_code ?></td>
                                    <td><?= $assignment->degree ?></td>
                                    <td><?= $assignment->teacher_name ?></td>
                                    <td>
                                        <button class="btn btn-primary btnUpdateStructure" data-id-assignment="<?= $assignment->id_assignment ?>" data-id-period-calendar="<?=$_GET['id_period_calendar']?>">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr ><td colspan="8" style="text-align: center; vertical-align: middle;"><h1>SIN RESULTADOS</h1></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        <?php endif; ?>
    </section><!-- /Featured Services Section -->

</main>
<script src="js/updateStructure.js"></script>