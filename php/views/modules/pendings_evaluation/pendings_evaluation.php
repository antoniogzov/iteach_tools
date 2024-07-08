<?php
$getLevelCombinations = $structure_model->getLevelCombinations();
$getPeriods = array();

?>
<main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section">
        <div class="hero-bg">
            <img src="assets/img/hero-bg-light.webp" alt="">
        </div>
        <div class="container text-center">
            <div class="d-flex flex-column justify-content-center align-items-center">
                <h1 data-aos="fade-up">Asignaturas pendientes <span> de sincronización</span></h1>
            </div>
        </div>

    </section><!-- /Hero Section -->

    <!-- Featured Services Section -->
    <section id="featured-services" class="featured-services section light-background">

        <br>
        <div class="container">
            <h1>Asignaturas sin Estructura de evaluación</h1>
            <br>
            <?php
            $assignments = $structure_model->getAssignmentsPendings();
            $ass_count = 0;
            ?>
            <div class="table-responsive">
                <a href="?submodule=automatic_eval_structure" class="btn btn-lg btn-primary" target="_blank" rel=""><i class="fas fa-sync"></i> Actualizar todas las estructuras pendientes </a>
                <br>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">ID</th>
                            <th scope="col">N° Periodo</th>
                            <th scope="col">Materia</th>
                            <th scope="col">Grupo</th>
                            <th scope="col">Nivel Académico</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assignments)) : ?>
                            <?php foreach ($assignments as $assignment) :
                                $ass_count++;
                            ?>
                                <tr>
                                    <th scope="row"><?= $ass_count ?></th>
                                    <th scope="row"><?= $assignment->id_assignment ?></th>
                                    <td><?= $assignment->no_period ?></td>
                                    <td><?= $assignment->name_subject ?></td>
                                    <td><?= $assignment->group_code ?></td>
                                    <td><?= $assignment->degree ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" style="text-align: center; vertical-align: middle;">
                                    <h1>SIN RESULTADOS</h1>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </section><!-- /Featured Services Section -->

</main>
<script src="js/updateStructure.js"></script>