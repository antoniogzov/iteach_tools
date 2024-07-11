<?php

require_once dirname(__DIR__ . '', 2) . '/Connection.php';

$cn = new data_conn();
$conexion = $cn->dbConn();
date_default_timezone_set('America/Mexico_City');

class evalStructure extends data_conn
{
    private $conn;
    public function __construct()
    {
        $this->conn = $this->dbConn();
    }

    public function getAssignments($id_level_combination, $id_period_calendar)
    {
        $results = array();

        $today = date('Y-m-d');



        $get_results = $this->conn->query("SELECT asg.id_assignment, sbj.name_subject, gps.group_code, aclg.degree, percal.id_period_calendar,
        UPPER(CONCAT(colab.apellido_paterno_colaborador, ' ', colab.apellido_materno_colaborador, ' ', colab.nombres_colaborador)) AS teacher_name
        FROM
        iteach_grades_quantitatives.period_calendar AS percal
        INNER JOIN school_control_ykt.level_combinations AS lvc ON lvc.id_level_combination = percal.id_level_combination
        INNER JOIN school_control_ykt.academic_levels AS acl ON acl.id_academic_level = lvc.id_academic_level
        INNER JOIN school_control_ykt.academic_levels_grade AS aclg ON aclg.id_academic_level = acl.id_academic_level
        INNER JOIN school_control_ykt.groups AS gps ON gps.id_level_grade = aclg.id_level_grade  AND lvc.id_campus = gps.id_campus AND gps.id_section = lvc.id_section
        INNER JOIN school_control_ykt.assignments AS asg ON asg.id_group = gps.id_group AND (show_list_teacher = 0 OR show_list_teacher = percal.no_period)
        INNER JOIN school_control_ykt.subjects AS sbj ON sbj.id_subject = asg.id_subject AND sbj.id_academic_area = lvc.id_academic_area
        INNER JOIN colaboradores_ykt.colaboradores AS colab ON colab.no_colaborador = asg.no_teacher
        WHERE lvc.id_level_combination = $id_level_combination AND percal.id_period_calendar = $id_period_calendar AND asg.assignment_active = 1 AND gps.is_active = 1 AND asg.print_school_report_card = 1
        ORDER BY asg.id_assignment
        ");




        $fga_structure = 0;

        while ($row_assignment = $get_results->fetch(PDO::FETCH_OBJ)) {

            $getEvalPlan = $this->conn->query("SELECT COUNT(id_evaluation_plan)
                FROM iteach_grades_quantitatives.evaluation_plan
                WHERE id_assignment = $row_assignment->id_assignment AND id_period_calendar = $id_period_calendar
                ")->fetchColumn();

            if ($getEvalPlan > 0) {
                $fga_structure = $this->conn->query("SELECT COUNT(ins.id_inscription)
                    FROM school_control_ykt.assignments AS assignment
                    INNER JOIN school_control_ykt.inscriptions AS ins ON assignment.id_group = ins.id_group AND ins.active = 1
                    INNER JOIN school_control_ykt.students AS student ON ins.id_student = student.id_student AND assignment.id_group = ins.id_group
                    LEFT JOIN iteach_grades_quantitatives.final_grades_assignment AS fg ON fg.id_inscription = ins.id_inscription AND ins.id_student = fg.id_student AND assignment.id_assignment = fg.id_assignment
                    WHERE assignment.id_assignment = $row_assignment->id_assignment AND student.status = 1 AND fg.id_student IS NULL
                    ")->fetchColumn();





                //--- PROCESO PARA VERIFICAR SI HAY QUE MOSTRAR ALGUNA MATERIA ADICIONAL ---//
                $add_assignment = $this->conn->query("SELECT COUNT(adt_std_assg.additional_registration_id)
                    FROM school_control_ykt.additional_registration_std_assg AS adt_std_assg
                    INNER JOIN school_control_ykt.students AS student ON adt_std_assg.id_student = student.id_student
                    INNER JOIN school_control_ykt.inscriptions AS ins ON adt_std_assg.id_group = ins.id_group AND adt_std_assg.id_student = ins.id_student AND student.group_id = adt_std_assg.id_group AND ins.active = 1
                    LEFT JOIN iteach_grades_quantitatives.final_grades_assignment AS fg ON ins.id_student = fg.id_student AND adt_std_assg.id_assignment = fg.id_assignment
                    WHERE adt_std_assg.id_assignment = $row_assignment->id_assignment AND student.status = 1 AND fg.id_student IS NULL")->fetchColumn();

                //--- PROCESO PARA VERIFICAR SI TODOS TIENEN LA ESTRUCTURA PARA ALMACENAR LOS PROMEDIOS POR PERIODOS ---//

                $fga_ass = 0;
                $fga_ass = $this->conn->query("SELECT COUNT(fg.id_final_grade)
                    FROM iteach_grades_quantitatives.final_grades_assignment AS fg
                    INNER JOIN school_control_ykt.assignments AS assignment ON fg.id_assignment = assignment.id_assignment
                    INNER JOIN school_control_ykt.inscriptions AS ins ON fg.id_inscription = ins.id_inscription AND assignment.id_group = ins.id_group AND ins.active = 1
                    INNER JOIN school_control_ykt.students AS student ON ins.id_student = student.id_student
                    LEFT JOIN iteach_grades_quantitatives.grades_period AS gp ON fg.id_final_grade = gp.id_final_grade AND gp.id_period_calendar = $row_assignment->id_period_calendar
                    WHERE fg.id_assignment = $row_assignment->id_assignment AND student.status = 1 AND gp.id_final_grade IS NULL")->fetchColumn();

                //--- PROCESO PARA VERIFICAR SI TODOS TIENEN LA ESTRUCTURA PARA ALMACENAR LOS PROMEDIOS POR PERIODOS ---//

                $ev_criteria = 0;
                $ev_criteria = $this->conn->query("SELECT COUNT(gp.id_grade_period)
                    FROM iteach_grades_quantitatives.final_grades_assignment AS fg 
                    INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON fg.id_assignment = evp.id_assignment 
                    INNER JOIN iteach_grades_quantitatives.grades_period AS gp ON gp.id_final_grade = fg.id_final_grade AND gp.id_period_calendar = evp.id_period_calendar
                    LEFT JOIN iteach_grades_quantitatives.grades_evaluation_criteria AS gec ON evp.id_evaluation_plan = gec.id_evaluation_plan AND gec.id_final_grade = fg.id_final_grade
                    WHERE evp.id_period_calendar = $row_assignment->id_period_calendar AND fg.id_assignment = $row_assignment->id_assignment  AND gec.id_final_grade IS NULL
                    ORDER BY fg.id_final_grade, evp.id_evaluation_plan")->fetchColumn();

                /*  echo "id_assignment: $row_assignment->id_assignment - id_period: $row_assignment->id_period_calendar - fga_structure: " . $fga_structure .
                        " add_assignment: " . $add_assignment .
                        " fga_ass: " . $fga_ass .
                        " ev_criteria: " . $ev_criteria;
        
                        echo "<br><br>"; */


                if ($fga_structure > 0 || $add_assignment > 0 || $fga_ass > 0 || $ev_criteria > 0) {

                    $results[] = $row_assignment;

                    $ev_pending = $this->conn->query("SELECT COUNT(id_iteach_pendings)
                    FROM automation_pending.iteach_pendings
                    WHERE id_period_calendar = $row_assignment->id_period_calendar AND id_assignment = $row_assignment->id_assignment  AND active = 1")->fetchColumn();

                    if ($ev_pending == 0) {
                        $this->conn->query("INSERT INTO automation_pending.iteach_pendings (id_assignment, id_period_calendar, id_pending_types, active) VALUES(
                                $row_assignment->id_assignment,
                                $row_assignment->id_period_calendar,
                                1,
                                1
                            )
                            ");
                    }
                }
            }
        }

        return $results;
    }
    public function getAllAssignments($id_level_combination, $id_period_calendar)
    {
        $results = array();

        $today = date('Y-m-d');



        $get_results = $this->conn->query("SELECT asg.id_assignment, sbj.name_subject, gps.group_code, aclg.degree, percal.id_period_calendar, percal.no_period,
        UPPER(CONCAT(colab.apellido_paterno_colaborador, ' ', colab.apellido_materno_colaborador, ' ', colab.nombres_colaborador)) AS teacher_name
        FROM
        iteach_grades_quantitatives.period_calendar AS percal
        INNER JOIN school_control_ykt.level_combinations AS lvc ON lvc.id_level_combination = percal.id_level_combination
        INNER JOIN school_control_ykt.academic_levels AS acl ON acl.id_academic_level = lvc.id_academic_level
        INNER JOIN school_control_ykt.academic_levels_grade AS aclg ON aclg.id_academic_level = acl.id_academic_level
        INNER JOIN school_control_ykt.groups AS gps ON gps.id_level_grade = aclg.id_level_grade  AND lvc.id_campus = gps.id_campus AND gps.id_section = lvc.id_section
        INNER JOIN school_control_ykt.assignments AS asg ON asg.id_group = gps.id_group AND (show_list_teacher = 0 OR show_list_teacher = percal.no_period)
        INNER JOIN school_control_ykt.subjects AS sbj ON sbj.id_subject = asg.id_subject AND sbj.id_academic_area = lvc.id_academic_area
        INNER JOIN colaboradores_ykt.colaboradores AS colab ON colab.no_colaborador = asg.no_teacher
        WHERE lvc.id_level_combination = $id_level_combination AND percal.id_period_calendar = $id_period_calendar
        ORDER BY asg.id_assignment
        ");




        $fga_structure = 0;

        while ($row_assignment = $get_results->fetch(PDO::FETCH_OBJ)) {

            $results[] = $row_assignment;
        }

        return $results;
    }
    public function getAssignmentsPendings()
    {
        $results = array();

        $today = date('Y-m-d');



        $get_results = $this->conn->query("SELECT asg.id_assignment, sbj.name_subject, gps.group_code, aclg.degree, percal.no_period
        FROM
        automation_pending.iteach_pendings AS pend
        INNER JOIN school_control_ykt.assignments AS asg ON pend.id_assignment = asg.id_assignment
        INNER JOIN school_control_ykt.groups AS gps ON  asg.id_group = gps.id_group
        INNER JOIN school_control_ykt.academic_levels_grade AS aclg ON gps.id_level_grade = aclg.id_level_grade
        INNER JOIN school_control_ykt.subjects AS sbj ON sbj.id_subject = asg.id_subject
        INNER JOIN iteach_grades_quantitatives.period_calendar AS percal ON percal.id_period_calendar = pend.id_period_calendar
        WHERE pend.active = 1
        ORDER BY asg.id_assignment
        ");


        while ($row_assignment = $get_results->fetch(PDO::FETCH_OBJ)) {
            $results[] = $row_assignment;
        }

        return $results;
    }
    public function getLevelCombinations()
    {
        $results = array();

        $today = date('Y-m-d');
        $todat = "2024-05-01";



        $get_results = $this->conn->query("SELECT UPPER(CONCAT(al.academic_level , ' | ', cmp.campus_name , ' | ', sct.section, ' | ', aca.name_academic_area  )) AS combination_name, id_level_combination
        FROM school_control_ykt.level_combinations AS lvlc
        INNER JOIN school_control_ykt.academic_levels AS al ON lvlc.id_academic_level = al.id_academic_level
        INNER JOIN school_control_ykt.campus AS cmp ON cmp.id_campus = lvlc.id_campus
        INNER JOIN school_control_ykt.sections AS sct ON sct.id_section = lvlc.id_section
        INNER JOIN school_control_ykt.academic_areas AS aca ON aca.id_academic_area = lvlc.id_academic_area
        ORDER BY lvlc.id_academic_level DESC
        ");

        $fga_structure = 0;
        while ($row_assignment = $get_results->fetch(PDO::FETCH_OBJ)) {
            $results[] = $row_assignment;
        }

        return $results;
    }
    public function getPeriods($id_level_combination)
    {
        $results = array();

        $today = date('Y-m-d');
        $todat = "2024-05-01";



        $get_results = $this->conn->query("SELECT percal.* FROM 
        iteach_grades_quantitatives.period_calendar AS percal
        WHERE percal.id_level_combination = $id_level_combination
        ");

        $fga_structure = 0;
        while ($row_assignment = $get_results->fetch(PDO::FETCH_OBJ)) {
            $results[] = $row_assignment;
        }

        return $results;
    }

    public function getAssignmentsForPendings()
    {
        $results = array();

        $today = date('Y-m-d');



        $get_results = $this->conn->query("SELECT DISTINCT asg.id_assignment, sbj.name_subject, gps.group_code, aclg.degree, evp.id_period_calendar,
        UPPER(CONCAT(colab.apellido_paterno_colaborador, ' ', colab.apellido_materno_colaborador, ' ', colab.nombres_colaborador)) AS teacher_name
        FROM school_control_ykt.assignments AS asg
        INNER JOIN school_control_ykt.groups AS gps ON gps.id_group = asg.id_group
        INNER JOIN school_control_ykt.academic_levels_grade AS aclg ON gps.id_level_grade = aclg.id_level_grade
        INNER JOIN school_control_ykt.subjects AS sbj ON sbj.id_subject = asg.id_subject
        INNER JOIN colaboradores_ykt.colaboradores AS colab ON colab.no_colaborador = asg.no_teacher
        INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON evp.id_assignment = asg.id_assignment
        WHERE asg.assignment_active = 1 AND gps.is_active = 1 AND asg.print_school_report_card = 1
        ORDER BY asg.id_assignment
        ");




        $fga_structure = 0;

        while ($row_assignment = $get_results->fetch(PDO::FETCH_OBJ)) {

            $getEvalPlan = $this->conn->query("SELECT COUNT(id_evaluation_plan)
                FROM iteach_grades_quantitatives.evaluation_plan
                WHERE id_assignment = $row_assignment->id_assignment
                ")->fetchColumn();

            if ($getEvalPlan > 0) {
                $fga_structure = $this->conn->query("SELECT COUNT(ins.id_inscription)
                    FROM school_control_ykt.assignments AS assignment
                    INNER JOIN school_control_ykt.inscriptions AS ins ON assignment.id_group = ins.id_group AND ins.active = 1
                    INNER JOIN school_control_ykt.students AS student ON ins.id_student = student.id_student AND assignment.id_group = ins.id_group
                    LEFT JOIN iteach_grades_quantitatives.final_grades_assignment AS fg ON fg.id_inscription = ins.id_inscription AND ins.id_student = fg.id_student AND assignment.id_assignment = fg.id_assignment
                    WHERE assignment.id_assignment = $row_assignment->id_assignment AND student.status = 1 AND fg.id_student IS NULL
                    ")->fetchColumn();





                //--- PROCESO PARA VERIFICAR SI HAY QUE MOSTRAR ALGUNA MATERIA ADICIONAL ---//
                $add_assignment = $this->conn->query("SELECT COUNT(adt_std_assg.additional_registration_id)
                    FROM school_control_ykt.additional_registration_std_assg AS adt_std_assg
                    INNER JOIN school_control_ykt.students AS student ON adt_std_assg.id_student = student.id_student
                    INNER JOIN school_control_ykt.inscriptions AS ins ON adt_std_assg.id_group = ins.id_group AND adt_std_assg.id_student = ins.id_student AND student.group_id = adt_std_assg.id_group AND ins.active = 1
                    LEFT JOIN iteach_grades_quantitatives.final_grades_assignment AS fg ON ins.id_student = fg.id_student AND adt_std_assg.id_assignment = fg.id_assignment
                    WHERE adt_std_assg.id_assignment = $row_assignment->id_assignment AND student.status = 1 AND fg.id_student IS NULL")->fetchColumn();

                //--- PROCESO PARA VERIFICAR SI TODOS TIENEN LA ESTRUCTURA PARA ALMACENAR LOS PROMEDIOS POR PERIODOS ---//

                $fga_ass = 0;
                $fga_ass = $this->conn->query("SELECT COUNT(fg.id_final_grade)
                    FROM iteach_grades_quantitatives.final_grades_assignment AS fg
                    INNER JOIN school_control_ykt.assignments AS assignment ON fg.id_assignment = assignment.id_assignment
                    INNER JOIN school_control_ykt.inscriptions AS ins ON fg.id_inscription = ins.id_inscription AND assignment.id_group = ins.id_group AND ins.active = 1
                    INNER JOIN school_control_ykt.students AS student ON ins.id_student = student.id_student
                    LEFT JOIN iteach_grades_quantitatives.grades_period AS gp ON fg.id_final_grade = gp.id_final_grade AND gp.id_period_calendar = $row_assignment->id_period_calendar
                    WHERE fg.id_assignment = $row_assignment->id_assignment AND student.status = 1 AND gp.id_final_grade IS NULL")->fetchColumn();

                //--- PROCESO PARA VERIFICAR SI TODOS TIENEN LA ESTRUCTURA PARA ALMACENAR LOS PROMEDIOS POR PERIODOS ---//

                $ev_criteria = 0;
                $ev_criteria = $this->conn->query("SELECT COUNT(gp.id_grade_period)
                    FROM iteach_grades_quantitatives.final_grades_assignment AS fg 
                    INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON fg.id_assignment = evp.id_assignment 
                    INNER JOIN iteach_grades_quantitatives.grades_period AS gp ON gp.id_final_grade = fg.id_final_grade AND gp.id_period_calendar = evp.id_period_calendar
                    LEFT JOIN iteach_grades_quantitatives.grades_evaluation_criteria AS gec ON evp.id_evaluation_plan = gec.id_evaluation_plan AND gec.id_final_grade = fg.id_final_grade
                    WHERE evp.id_period_calendar = $row_assignment->id_period_calendar AND fg.id_assignment = $row_assignment->id_assignment  AND gec.id_final_grade IS NULL
                    ORDER BY fg.id_final_grade, evp.id_evaluation_plan")->fetchColumn();

                /*  echo "id_assignment: $row_assignment->id_assignment - id_period: $row_assignment->id_period_calendar - fga_structure: " . $fga_structure .
                        " add_assignment: " . $add_assignment .
                        " fga_ass: " . $fga_ass .
                        " ev_criteria: " . $ev_criteria;
        
                        echo "<br><br>"; */


                if ($fga_structure > 0 || $add_assignment > 0 || $fga_ass > 0 || $ev_criteria > 0) {

                    $results[] = $row_assignment;

                    $ev_pending = $this->conn->query("SELECT COUNT(id_iteach_pendings)
                    FROM automation_pending.iteach_pendings
                    WHERE id_period_calendar = $row_assignment->id_period_calendar AND id_assignment = $row_assignment->id_assignment  AND active = 1")->fetchColumn();

                    if ($ev_pending == 0) {
                        $this->conn->query("INSERT INTO automation_pending.iteach_pendings (id_assignment, id_period_calendar, id_pending_types, active) VALUES(
                                $row_assignment->id_assignment,
                                $row_assignment->id_period_calendar,
                                1,
                                1
                            )
                            ");
                    }
                }
            }
        }

        return $results;
    }
}
