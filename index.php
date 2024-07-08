
   <?php


    include 'php/models/modules/eval_structure/eval_structure.php';
    include 'php/models/modules/automatic_eval_structure/automatic_eval_structure.php';


    $structure_model  = new evalStructure;
    $automatic_structure_model  = new evalStructureAutomatic;

    include 'php/views/head.php';
    include 'php/views/navbar.php';


    if (isset($_GET['submodule'])) {
        $submodule = $_GET['submodule'];
        switch ($submodule) {


            case 'eval_structure':
                $include_file = 'php/views/modules/eval_structure/eval_structure.php';
                break;
            case 'automatic_eval_structure':
                $include_file = 'php/views/modules/automatic_structure/automatic_eval_structure.php';

                break;

            case 'pendings_evaluation':
                $include_file = 'php/views/modules/pendings_evaluation/pendings_evaluation.php';
                break;




            default:

                break;
        }
    } else {
    }

    if (!isset($_GET['submodule'])) {
        include 'php/views/main_index.php';
    } else {
        include $include_file;
    }
    include 'php/views/footer.php';
    include 'php/views/endpage.php';
    ?>

