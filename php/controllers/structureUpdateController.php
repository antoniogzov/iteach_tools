<?php
include '../models/querysModel.php';

session_start();
date_default_timezone_set('America/Mexico_City');
$function = $_POST['mod'];
$function();


function getPeriodsCalendar()
{

    $id_level_combination = $_POST['id_level_combination'];

    $queries = new Queries;


    /* INSERTAR CATALOGO DE AE  */
    $stmt = "SELECT percal.* FROM 
    iteach_grades_quantitatives.period_calendar AS percal
    WHERE percal.id_level_combination = $id_level_combination";
    $periods = $queries->getData($stmt);

    $options = "<option selected disabled>Seleccione un periodo</option>";
    foreach ($periods as $period) {
        $options .= "<option value='$period->id_period_calendar'>$period->no_period</option>";
    }

    if (!empty($periods)) {

        $data = array(
            'response' => true,
            'options' => $options
        );
    } else {
        $data = array(
            'response' => false,
            'message' => 'Al parecer no hay ningún criterio de aprendizaje esperado en común'
        );
    }

    echo json_encode($data);
}
function createStructureQualificationsMassive()
{

    $id_assignment = $_POST['id_assignment'];
    $id_period_calendar = $_POST['id_period_calendar'];


    $queries = new Queries();
    $getPeriods = array();

    $stmt = "SELECT lc.id_level_combination
        FROM school_control_ykt.level_combinations AS lc
        INNER JOIN school_control_ykt.groups AS groups ON groups.id_campus = lc.id_campus
        INNER JOIN school_control_ykt.assignments AS assignment ON groups.id_group = assignment.id_group
        INNER JOIN school_control_ykt.academic_levels_grade AS ac_le_gra ON groups.id_level_grade = ac_le_gra.id_level_grade
        INNER JOIN school_control_ykt.academic_levels AS ac_le ON ac_le_gra.id_academic_level = ac_le.id_academic_level
        INNER JOIN school_control_ykt.subjects AS subject ON assignment.id_subject = subject.id_subject
        WHERE (lc.id_section = groups.id_section OR lc.id_section = 3) AND lc.id_campus = groups.id_campus AND lc.id_academic_level = ac_le.id_academic_level AND lc.id_academic_area = subject.id_academic_area AND assignment.id_assignment = $id_assignment LIMIT 1";

    $geLVLC = $queries->getData($stmt);

    foreach ($geLVLC as $level_combinations) {
        $id_level_combination = $level_combinations->id_level_combination;
    }
    $sql_level_combinations = "SELECT * FROM iteach_grades_quantitatives.period_calendar WHERE id_level_combination = $id_level_combination AND id_period_calendar = $id_period_calendar";
    $getPeriods = $queries->getData($sql_level_combinations);

    foreach ($getPeriods as $periods) {
        $id_period = $periods->id_period_calendar;
        
    }
    createStructureQualificationsByPeriod($id_assignment, $id_period_calendar);

   
    $data = array(
        'response' => true,
        'message' => 'Se actualizó la hoja de trabajo'
    );

    echo json_encode($data);
}

function createStructureQualificationsByPeriod($id_assignment, $id_period)
{

    $queries = new Queries();

    $queryFGA = "SELECT ins.id_inscription, ins.id_student, student.student_code
FROM school_control_ykt.assignments AS assignment
INNER JOIN school_control_ykt.inscriptions AS ins ON assignment.id_group = ins.id_group AND ins.active = 1
INNER JOIN school_control_ykt.students AS student ON ins.id_student = student.id_student AND assignment.id_group = ins.id_group
LEFT JOIN iteach_grades_quantitatives.final_grades_assignment AS fg ON fg.id_inscription = ins.id_inscription AND ins.id_student = fg.id_student AND assignment.id_assignment = fg.id_assignment
WHERE assignment.id_assignment = $id_assignment AND student.status = 1 AND fg.id_student IS NULL";
    $getFGAStructure = $queries->getData($queryFGA);

    foreach ($getFGAStructure as $fga) {
        $id_inscription = $fga->id_inscription;
        $id_student = $fga->id_student;
        $student_code = $fga->student_code;

        $stmtFGA = ("INSERT INTO iteach_grades_quantitatives.final_grades_assignment (id_inscription, id_assignment, id_student, student_code) VALUES ($id_inscription, $id_assignment, $id_student, '$student_code')");
        $queries->InsertData($stmtFGA);
    }


    //--- PROCESO PARA VERIFICAR SI HAY QUE MOSTRAR ALGUNA MATERIA ADICIONAL ---//
    $queryFGAEx = "SELECT ins.id_inscription, adt_std_assg.additional_registration_id, ins.id_student, student.student_code
FROM school_control_ykt.additional_registration_std_assg AS adt_std_assg
INNER JOIN school_control_ykt.students AS student ON adt_std_assg.id_student = student.id_student
INNER JOIN school_control_ykt.inscriptions AS ins ON adt_std_assg.id_group = ins.id_group AND adt_std_assg.id_student = ins.id_student AND student.group_id = adt_std_assg.id_group AND ins.active = 1
LEFT JOIN iteach_grades_quantitatives.final_grades_assignment AS fg ON ins.id_student = fg.id_student AND adt_std_assg.id_assignment = fg.id_assignment
WHERE adt_std_assg.id_assignment = $id_assignment AND student.status = 1 AND fg.id_student IS NULL";
    $getFGAExStructure = $queries->getData($queryFGAEx);

    foreach ($getFGAExStructure as $fga_ex) {
        $id_inscription = $fga_ex->id_inscription;
        $additional_registration_id = $fga_ex->additional_registration_id;
        $id_student = $fga_ex->id_student;
        $student_code = $fga_ex->student_code;

        $stmtFGAEx = ("INSERT INTO iteach_grades_quantitatives.final_grades_assignment (id_inscription, additional_registration_id, id_assignment, id_student, student_code) VALUES ($id_inscription, $additional_registration_id, $id_assignment, $id_student, '$student_code')");
        $queries->getData($stmtFGAEx);
    }

    //--- PROCESO PARA VERIFICAR SI TODOS TIENEN LA ESTRUCTURA PARA ALMACENAR LOS PROMEDIOS POR PERIODOS ---//

    $queryPerCal = "SELECT fg.id_final_grade
FROM iteach_grades_quantitatives.final_grades_assignment AS fg
INNER JOIN school_control_ykt.assignments AS assignment ON fg.id_assignment = assignment.id_assignment
INNER JOIN school_control_ykt.inscriptions AS ins ON fg.id_inscription = ins.id_inscription AND assignment.id_group = ins.id_group AND ins.active = 1
INNER JOIN school_control_ykt.students AS student ON ins.id_student = student.id_student
LEFT JOIN iteach_grades_quantitatives.grades_period AS gp ON fg.id_final_grade = gp.id_final_grade AND gp.id_period_calendar = $id_period
WHERE fg.id_assignment = $id_assignment AND student.status = 1 AND gp.id_final_grade IS NULL";
    $getPerCalStructure = $queries->getData($queryPerCal);

    $get_no_period = $queries->getData("SELECT no_period FROM iteach_grades_quantitatives.period_calendar WHERE id_period_calendar = $id_period");

    if (count($get_no_period) > 0) {
        $no_period = $get_no_period[0]->no_period;
        foreach ($getPerCalStructure as $grape) {
            $id_final_grade = $grape->id_final_grade;

            $stmtGRAPE = ("INSERT INTO iteach_grades_quantitatives.grades_period (id_final_grade, id_period_calendar, no_period) VALUES ($id_final_grade, $id_period, $no_period)");
            $queries->getData($stmtGRAPE);
        }
    }

    //--- PROCESO PARA VERIFICAR SI TODOS TIENEN LA ESTRUCTURA PARA ALMACENAR LOS PROMEDIOS POR PERIODOS ---//
    $stmt = " SELECT gp.id_grade_period, evp.id_evaluation_plan, fg.id_final_grade, fg.id_student
FROM iteach_grades_quantitatives.final_grades_assignment AS fg 
INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON fg.id_assignment = evp.id_assignment 
INNER JOIN iteach_grades_quantitatives.grades_period AS gp ON gp.id_final_grade = fg.id_final_grade AND gp.id_period_calendar = evp.id_period_calendar
LEFT JOIN iteach_grades_quantitatives.grades_evaluation_criteria AS gec ON evp.id_evaluation_plan = gec.id_evaluation_plan AND gec.id_final_grade = fg.id_final_grade
WHERE evp.id_period_calendar = $id_period AND fg.id_assignment = $id_assignment  AND gec.id_final_grade IS NULL
ORDER BY fg.id_final_grade, evp.id_evaluation_plan";
    $catalog_item = $queries->getData($stmt);


    if (!empty($catalog_item)) {

        foreach ($catalog_item as $row) {
            $id_grade_period = $row->id_grade_period;
            $id_evaluation_plan = $row->id_evaluation_plan;
            $id_final_grade = $row->id_final_grade;

            $id_student = $row->id_student;

            $stmt = ("INSERT INTO iteach_grades_quantitatives.grades_evaluation_criteria (id_grade_period, id_evaluation_plan, id_final_grade) VALUES ($id_grade_period, $id_evaluation_plan, $id_final_grade)");
            $queries->getData($stmt);

            $smtCOGTH = ("SELECT conf_gat.*
        FROM iteach_grades_quantitatives.conf_grade_gathering AS conf_gat
        INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON conf_gat.id_evaluation_plan = evp.id_evaluation_plan
        WHERE evp.id_evaluation_plan = $id_evaluation_plan AND evp.gathering = 1
        ");
            $getConfGathering = $queries->getData($smtCOGTH);

            foreach ($getConfGathering as $confggath) {
                $id_conf_grade_gathering = $confggath->id_conf_grade_gathering;
                $stmtGath = " SELECT *
            FROM iteach_grades_quantitatives.grades_evaluation_criteria AS gec
            INNER JOIN iteach_grades_quantitatives.conf_grade_gathering AS conf_gg ON gec.id_evaluation_plan = conf_gg.id_evaluation_plan
            INNER JOIN iteach_grades_quantitatives.grade_gathering AS gg ON conf_gg.id_conf_grade_gathering = gg.id_conf_grade_gathering
            INNER JOIN iteach_grades_quantitatives.final_grades_assignment fga ON gg.id_final_grade = fga.id_final_grade
            WHERE gg.id_conf_grade_gathering = $id_conf_grade_gathering AND fga.id_student = $id_student AND gec.id_grades_evaluation_criteria = gg.id_grades_evaluation_criteria";

                if (empty($queries->getData($stmtGath))) {

                    //--- PROCESO PARA VERIFICAR SI HAY QUE MOSTRAR ALGUNA MATERIA ADICIONAL ---//
                    $queryGRGTH = "SELECT conf_gg.id_conf_grade_gathering, conf_gg.id_evaluation_plan, gec.id_final_grade, gec.id_grades_evaluation_criteria
                FROM iteach_grades_quantitatives.grades_evaluation_criteria AS gec
                INNER JOIN iteach_grades_quantitatives.conf_grade_gathering AS conf_gg ON gec.id_evaluation_plan = conf_gg.id_evaluation_plan
                INNER JOIN iteach_grades_quantitatives.final_grades_assignment fga ON gec.id_final_grade = fga.id_final_grade
                WHERE conf_gg.id_conf_grade_gathering = $id_conf_grade_gathering AND fga.id_student = $id_student";
                    $getGraGthStructure = $queries->getData($queryGRGTH);

                    foreach ($getGraGthStructure as $grath) {

                        $id_conf_grade_gathering = $grath->id_conf_grade_gathering;
                        $id_evaluation_plan      = $grath->id_evaluation_plan;
                        $id_final_grade          = $grath->id_final_grade;
                        $id_grades_evaluation_criteria = $grath->id_grades_evaluation_criteria;

                        $stmtGraGth = ("INSERT INTO iteach_grades_quantitatives.grade_gathering (id_conf_grade_gathering, id_evaluation_plan, id_grades_evaluation_criteria, id_final_grade) VALUES ('$id_conf_grade_gathering', '$id_evaluation_plan', '$id_grades_evaluation_criteria', '$id_final_grade')");
                        $queries->getData($stmtGraGth);
                    }
                }
            }
        }

        $data = array(
            'response' => true,
            'message' => 'Se actualizó la hoja de trabajo'
        );
    } else {

        $stmt = " SELECT gp.id_grade_period, evp.id_evaluation_plan, fg.id_final_grade, fg.id_student
    FROM iteach_grades_quantitatives.final_grades_assignment AS fg 
    INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON fg.id_assignment = evp.id_assignment 
    INNER JOIN iteach_grades_quantitatives.grades_period AS gp ON gp.id_final_grade = fg.id_final_grade AND gp.id_period_calendar = evp.id_period_calendar
    LEFT JOIN iteach_grades_quantitatives.grades_evaluation_criteria AS gec ON evp.id_evaluation_plan = gec.id_evaluation_plan AND gec.id_final_grade = fg.id_final_grade
    WHERE evp.id_period_calendar = $id_period AND fg.id_assignment = $id_assignment  AND gec.id_final_grade IS NOT NULL
    ORDER BY fg.id_final_grade, evp.id_evaluation_plan";
        $catalog_item = $queries->getData($stmt);

        foreach ($catalog_item as $row) {
            $id_grade_period = $row->id_grade_period;
            $id_evaluation_plan = $row->id_evaluation_plan;
            $id_final_grade = $row->id_final_grade;

            $id_student = $row->id_student;

            $smtCOGTH = ("SELECT conf_gat.*
        FROM iteach_grades_quantitatives.conf_grade_gathering AS conf_gat
        INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON conf_gat.id_evaluation_plan = evp.id_evaluation_plan
        WHERE evp.id_evaluation_plan = $id_evaluation_plan AND evp.gathering = 1
        ");
            $getConfGathering = $queries->getData($smtCOGTH);

            foreach ($getConfGathering as $confggath) {
                $id_conf_grade_gathering = $confggath->id_conf_grade_gathering;
                $stmtGath = " SELECT *
            FROM iteach_grades_quantitatives.grades_evaluation_criteria AS gec
            INNER JOIN iteach_grades_quantitatives.conf_grade_gathering AS conf_gg ON gec.id_evaluation_plan = conf_gg.id_evaluation_plan
            INNER JOIN iteach_grades_quantitatives.grade_gathering AS gg ON conf_gg.id_conf_grade_gathering = gg.id_conf_grade_gathering
            INNER JOIN iteach_grades_quantitatives.final_grades_assignment fga ON gg.id_final_grade = fga.id_final_grade
            WHERE gg.id_conf_grade_gathering = $id_conf_grade_gathering AND fga.id_student = $id_student AND gec.id_grades_evaluation_criteria = gg.id_grades_evaluation_criteria";

                if (empty($queries->getData($stmtGath))) {

                    //--- PROCESO PARA VERIFICAR SI HAY QUE MOSTRAR ALGUNA MATERIA ADICIONAL ---//
                    $queryGRGTH = "SELECT conf_gg.id_conf_grade_gathering, conf_gg.id_evaluation_plan, gec.id_final_grade, gec.id_grades_evaluation_criteria
                FROM iteach_grades_quantitatives.grades_evaluation_criteria AS gec
                INNER JOIN iteach_grades_quantitatives.conf_grade_gathering AS conf_gg ON gec.id_evaluation_plan = conf_gg.id_evaluation_plan
                INNER JOIN iteach_grades_quantitatives.final_grades_assignment fga ON gec.id_final_grade = fga.id_final_grade
                WHERE conf_gg.id_conf_grade_gathering = $id_conf_grade_gathering AND fga.id_student = $id_student";
                    $getGraGthStructure = $queries->getData($queryGRGTH);

                    foreach ($getGraGthStructure as $grath) {

                        $id_conf_grade_gathering = $grath->id_conf_grade_gathering;
                        $id_evaluation_plan      = $grath->id_evaluation_plan;
                        $id_final_grade          = $grath->id_final_grade;
                        $id_grades_evaluation_criteria = $grath->id_grades_evaluation_criteria;

                        $stmtGraGth = ("INSERT INTO iteach_grades_quantitatives.grade_gathering (id_conf_grade_gathering, id_evaluation_plan, id_grades_evaluation_criteria, id_final_grade) VALUES ('$id_conf_grade_gathering', '$id_evaluation_plan', '$id_grades_evaluation_criteria', '$id_final_grade')");
                        $queries->getData($stmtGraGth);
                    }
                }
            }
        }
        $stmtGraGth = ("INSERT INTO audits.iteach (
        no_teacher,
        table_,
        column_,
        id_column,
        action_,
        id_assignment,
        id_period_calendar,
        additional_comments,
        date_log
        ) VALUES (
            1115,
            'iteach_grades_quantitatives.final_grades_assignment',
             'N/A',
             'N/A',
             'generate_evaluation_structure',
             $id_assignment,
             $id_period,
             '',
             NOW()
             )");
        $queries->getData($stmtGraGth);
        $data = array(
            'response' => false,
            'message' => 'Se actualizó la hoja de trabajo'
        );
    }

    //  echo json_encode($data);
}