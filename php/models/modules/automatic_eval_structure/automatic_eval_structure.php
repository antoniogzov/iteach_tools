<?php
set_time_limit(0);
require_once dirname(__DIR__ . '', 2) . '/Connection.php';

$cn = new data_conn();
$conexion = $cn->dbConn();
date_default_timezone_set('America/Mexico_City');


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require dirname(__DIR__ . '', 4) . '/assets/vendor/autoload.php';





class evalStructureAutomatic extends data_conn
{
    private $conn;
    public function __construct()
    {
        $this->conn = $this->dbConn();
    }

    public function getAssignmentsAutomatic()
    {
        $results = array();

        $today = date('Y-m-d');
        // Disparar cronometro  
        $start_time = microtime(true);
        //El codigo a medir aqui , o funcion o invocacion  
        //a clase. Por ejemplo: 
        $a = 1;




        $get_results = $this->conn->query("SELECT * FROM automation_pending.iteach_pendings WHERE active = 1");




        $fga_structure = 0;
        $html = '';
        $html_brgin = "<br><h1>ATENCIÓN: Se ha comenzado el proceso de actualización automática de las hojas de trabajo.</h1>";
        $this->sendMailBeginProccess($html_brgin);
        while ($row_assignment = $get_results->fetch(PDO::FETCH_OBJ)) {

            $getEvalPlan = $this->conn->query("SELECT COUNT(id_evaluation_plan)
                FROM iteach_grades_quantitatives.evaluation_plan
                WHERE id_assignment = $row_assignment->id_assignment AND id_period_calendar = $row_assignment->id_period_calendar
                ")->fetchColumn();

            if ($getEvalPlan > 0) {


                $get_results_data = $this->conn->query("SELECT asg.id_assignment, sbj.name_subject, gps.group_code, aclg.degree, percal.id_period_calendar,
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
                WHERE asg.id_assignment = $row_assignment->id_assignment AND percal.id_period_calendar = $row_assignment->id_period_calendar
                ORDER BY asg.id_assignment
                ");


                while ($row_assignment_data = $get_results_data->fetch(PDO::FETCH_OBJ)) {
                    $results[] = $row_assignment_data;
                    echo "";
                    echo "<br><h1>ASIGNATURA: $row_assignment_data->name_subject | $row_assignment_data->group_code | $row_assignment->id_assignment</h1>";
                    $html .= "<br><h1>ASIGNATURA: $row_assignment_data->name_subject | $row_assignment_data->group_code | $row_assignment->id_assignment</h1>";
                }
                $this->createStructureQualificationsByPeriod1($row_assignment->id_assignment, $row_assignment->id_period_calendar);
                $this->conn->query("UPDATE automation_pending.iteach_pendings SET active = 0
                WHERE id_assignment = $row_assignment->id_assignment AND id_period_calendar = $row_assignment->id_period_calendar AND active = 1");
                $this->conn->query("INSERT INTO audits.iteach (
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
                        1140,
                        'iteach_grades_quantitatives.final_grades_assignment',
                         'N/A',
                         'N/A',
                         'generate_evaluation_structure',
                         $row_assignment->id_assignment,
                         $row_assignment->id_period_calendar,
                         '',
                         NOW()
                         )");

                $a++;
            }
        }
        // Calcular tiempo demorado  
        $end_time = (microtime(true) - $start_time);
        $time = $this->segundos_tiempo($end_time);
        echo 'Segundos: ' . $end_time . ' Resultado: ' . $time;
        $time_txt = " | Tiempo de ejecución = " . $time . "";
        echo " <h1><strong>Tiempo de ejecucion = " . $time . " </strong></h1>";
        $html1 = "<br><br> <h1><strong>Tiempo de ejecución = " . $time . " </strong></h1>";
        $html1 .= "<br>" . $html;

        $this->sendMailStructure($html1, $time_txt);

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

    public function createStructureQualificationsByPeriod1($id_assignment, $id_period)
    {

        $queryFGA = "SELECT ins.id_inscription, ins.id_student, student.student_code
            FROM school_control_ykt.assignments AS assignment
            INNER JOIN school_control_ykt.inscriptions AS ins ON assignment.id_group = ins.id_group AND ins.active = 1
            INNER JOIN school_control_ykt.students AS student ON ins.id_student = student.id_student AND assignment.id_group = ins.id_group
            LEFT JOIN iteach_grades_quantitatives.final_grades_assignment AS fg ON fg.id_inscription = ins.id_inscription AND ins.id_student = fg.id_student AND assignment.id_assignment = fg.id_assignment
            WHERE assignment.id_assignment = $id_assignment AND student.status = 1 AND fg.id_student IS NULL";

        $getFGAStructure = $this->conn->query($queryFGA);

        while ($fga = $getFGAStructure->fetch(PDO::FETCH_OBJ)) {
            $id_inscription = $fga->id_inscription;
            $id_student = $fga->id_student;
            $student_code = $fga->student_code;

            $stmtFGA = ("INSERT INTO iteach_grades_quantitatives.final_grades_assignment (id_inscription, id_assignment, id_student, student_code) VALUES ($id_inscription, $id_assignment, $id_student, '$student_code')");
            $this->conn->query($stmtFGA);
        }


        //--- PROCESO PARA VERIFICAR SI HAY QUE MOSTRAR ALGUNA MATERIA ADICIONAL ---//
        $queryFGAEx = "SELECT ins.id_inscription, adt_std_assg.additional_registration_id, ins.id_student, student.student_code
            FROM school_control_ykt.additional_registration_std_assg AS adt_std_assg
            INNER JOIN school_control_ykt.students AS student ON adt_std_assg.id_student = student.id_student
            INNER JOIN school_control_ykt.inscriptions AS ins ON adt_std_assg.id_group = ins.id_group AND adt_std_assg.id_student = ins.id_student AND student.group_id = adt_std_assg.id_group AND ins.active = 1
            LEFT JOIN iteach_grades_quantitatives.final_grades_assignment AS fg ON ins.id_student = fg.id_student AND adt_std_assg.id_assignment = fg.id_assignment
            WHERE adt_std_assg.id_assignment = $id_assignment AND student.status = 1 AND fg.id_student IS NULL";

        $getFGAExStructure = $this->conn->query($queryFGAEx);

        while ($fga_ex = $getFGAExStructure->fetch(PDO::FETCH_OBJ)) {

            $id_inscription = $fga_ex->id_inscription;
            $additional_registration_id = $fga_ex->additional_registration_id;
            $id_student = $fga_ex->id_student;
            $student_code = $fga_ex->student_code;

            $stmtFGAEx = ("INSERT INTO iteach_grades_quantitatives.final_grades_assignment (id_inscription, additional_registration_id, id_assignment, id_student, student_code) VALUES ($id_inscription, $additional_registration_id, $id_assignment, $id_student, '$student_code')");
            $this->conn->query($stmtFGAEx);
        }

        //--- PROCESO PARA VERIFICAR SI TODOS TIENEN LA ESTRUCTURA PARA ALMACENAR LOS PROMEDIOS POR PERIODOS ---//

        $queryPerCal = "SELECT fg.id_final_grade
            FROM iteach_grades_quantitatives.final_grades_assignment AS fg
            INNER JOIN school_control_ykt.assignments AS assignment ON fg.id_assignment = assignment.id_assignment
            INNER JOIN school_control_ykt.inscriptions AS ins ON fg.id_inscription = ins.id_inscription AND assignment.id_group = ins.id_group AND ins.active = 1
            INNER JOIN school_control_ykt.students AS student ON ins.id_student = student.id_student
            LEFT JOIN iteach_grades_quantitatives.grades_period AS gp ON fg.id_final_grade = gp.id_final_grade AND gp.id_period_calendar = $id_period
            WHERE fg.id_assignment = $id_assignment AND student.status = 1 AND gp.id_final_grade IS NULL";

        $getPerCalStructure = $this->conn->query($queryPerCal);
        $get_no_period = $this->conn->query("SELECT no_period FROM iteach_grades_quantitatives.period_calendar WHERE id_period_calendar = $id_period");

        while ($g_no_period = $get_no_period->fetch(PDO::FETCH_OBJ)) {
            $no_period = $g_no_period->no_period;
            while ($grape = $getPerCalStructure->fetch(PDO::FETCH_OBJ)) {

                $id_final_grade = $grape->id_final_grade;

                $stmtGRAPE = ("INSERT INTO iteach_grades_quantitatives.grades_period (id_final_grade, id_period_calendar, no_period) VALUES ($id_final_grade, $id_period, $no_period)");
                $this->conn->query($stmtGRAPE);
            }
        }


        //--- PROCESO PARA VERIFICAR SI TODOS TIENEN LA ESTRUCTURA PARA ALMACENAR LOS PROMEDIOS POR PERIODOS ---//

        $stmt = "SELECT gp.id_grade_period, evp.id_evaluation_plan, fg.id_final_grade, fg.id_student
        FROM iteach_grades_quantitatives.final_grades_assignment AS fg 
        INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON fg.id_assignment = evp.id_assignment 
        INNER JOIN iteach_grades_quantitatives.grades_period AS gp ON gp.id_final_grade = fg.id_final_grade AND gp.id_period_calendar = evp.id_period_calendar
        LEFT JOIN iteach_grades_quantitatives.grades_evaluation_criteria AS gec ON evp.id_evaluation_plan = gec.id_evaluation_plan AND gec.id_final_grade = fg.id_final_grade
        WHERE evp.id_period_calendar = $id_period AND fg.id_assignment = $id_assignment  AND gec.id_final_grade IS NULL
        ORDER BY fg.id_final_grade, evp.id_evaluation_plan";

        $catalog_item = $this->conn->query($stmt);

        while ($row = $catalog_item->fetch(PDO::FETCH_OBJ)) {


            $id_grade_period = $row->id_grade_period;
            $id_evaluation_plan = $row->id_evaluation_plan;
            $id_final_grade = $row->id_final_grade;

            $id_student = $row->id_student;

            $stmt = ("INSERT INTO iteach_grades_quantitatives.grades_evaluation_criteria (id_grade_period, id_evaluation_plan, id_final_grade) VALUES ($id_grade_period, $id_evaluation_plan, $id_final_grade)");
            $this->conn->query($stmt);

            $smtCOGTH = ("SELECT conf_gat.*
    FROM iteach_grades_quantitatives.conf_grade_gathering AS conf_gat
    INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON conf_gat.id_evaluation_plan = evp.id_evaluation_plan
    WHERE evp.id_evaluation_plan = $id_evaluation_plan AND evp.gathering = 1
    ");
            $getConfGathering = $this->conn->query($smtCOGTH);

            while ($confggath = $getConfGathering->fetch(PDO::FETCH_OBJ)) {

                $id_conf_grade_gathering = $confggath->id_conf_grade_gathering;

                $ev_criteria = $this->conn->query("SELECT COUNT(*)
                FROM iteach_grades_quantitatives.grades_evaluation_criteria AS gec
                INNER JOIN iteach_grades_quantitatives.conf_grade_gathering AS conf_gg ON gec.id_evaluation_plan = conf_gg.id_evaluation_plan
                INNER JOIN iteach_grades_quantitatives.grade_gathering AS gg ON conf_gg.id_conf_grade_gathering = gg.id_conf_grade_gathering
                INNER JOIN iteach_grades_quantitatives.final_grades_assignment fga ON gg.id_final_grade = fga.id_final_grade
                WHERE gg.id_conf_grade_gathering = $id_conf_grade_gathering AND fga.id_student = $id_student AND gec.id_grades_evaluation_criteria = gg.id_grades_evaluation_criteria")->fetchColumn();

                if ($ev_criteria == 0) {

                    //--- PROCESO PARA VERIFICAR SI HAY QUE MOSTRAR ALGUNA MATERIA ADICIONAL ---//
                    $queryGRGTH = "SELECT conf_gg.id_conf_grade_gathering, conf_gg.id_evaluation_plan, gec.id_final_grade, gec.id_grades_evaluation_criteria
            FROM iteach_grades_quantitatives.grades_evaluation_criteria AS gec
            INNER JOIN iteach_grades_quantitatives.conf_grade_gathering AS conf_gg ON gec.id_evaluation_plan = conf_gg.id_evaluation_plan
            INNER JOIN iteach_grades_quantitatives.final_grades_assignment fga ON gec.id_final_grade = fga.id_final_grade
            WHERE conf_gg.id_conf_grade_gathering = $id_conf_grade_gathering AND fga.id_student = $id_student";

                    $getGraGthStructure = $this->conn->query($queryGRGTH);

                    while ($grath = $getGraGthStructure->fetch(PDO::FETCH_OBJ)) {


                        $id_conf_grade_gathering = $grath->id_conf_grade_gathering;
                        $id_evaluation_plan      = $grath->id_evaluation_plan;
                        $id_final_grade          = $grath->id_final_grade;
                        $id_grades_evaluation_criteria = $grath->id_grades_evaluation_criteria;

                        $stmtGraGth = ("INSERT INTO iteach_grades_quantitatives.grade_gathering (id_conf_grade_gathering, id_evaluation_plan, id_grades_evaluation_criteria, id_final_grade) VALUES ('$id_conf_grade_gathering', '$id_evaluation_plan', '$id_grades_evaluation_criteria', '$id_final_grade')");
                        $this->conn->query($stmtGraGth);
                    }
                }
            }
        }


        $count_item = $this->conn->query("SELECT COUNT(*)
        FROM iteach_grades_quantitatives.final_grades_assignment AS fg 
        INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON fg.id_assignment = evp.id_assignment 
        INNER JOIN iteach_grades_quantitatives.grades_period AS gp ON gp.id_final_grade = fg.id_final_grade AND gp.id_period_calendar = evp.id_period_calendar
        LEFT JOIN iteach_grades_quantitatives.grades_evaluation_criteria AS gec ON evp.id_evaluation_plan = gec.id_evaluation_plan AND gec.id_final_grade = fg.id_final_grade
        WHERE evp.id_period_calendar = $id_period AND fg.id_assignment = $id_assignment  AND gec.id_final_grade IS NULL
        ORDER BY fg.id_final_grade, evp.id_evaluation_plan")->fetchColumn();

        if ($count_item == 0) {



            $stmt = " SELECT gp.id_grade_period, evp.id_evaluation_plan, fg.id_final_grade, fg.id_student
    FROM iteach_grades_quantitatives.final_grades_assignment AS fg 
    INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON fg.id_assignment = evp.id_assignment 
    INNER JOIN iteach_grades_quantitatives.grades_period AS gp ON gp.id_final_grade = fg.id_final_grade AND gp.id_period_calendar = evp.id_period_calendar
    LEFT JOIN iteach_grades_quantitatives.grades_evaluation_criteria AS gec ON evp.id_evaluation_plan = gec.id_evaluation_plan AND gec.id_final_grade = fg.id_final_grade
    WHERE evp.id_period_calendar = $id_period AND fg.id_assignment = $id_assignment  AND gec.id_final_grade IS NOT NULL
    ORDER BY fg.id_final_grade, evp.id_evaluation_plan";
            $catalog_item = $this->conn->query($stmt);

            while ($row = $catalog_item->fetch(PDO::FETCH_OBJ)) {

                $id_grade_period = $row->id_grade_period;
                $id_evaluation_plan = $row->id_evaluation_plan;
                $id_final_grade = $row->id_final_grade;

                $id_student = $row->id_student;

                $smtCOGTH = ("SELECT conf_gat.*
        FROM iteach_grades_quantitatives.conf_grade_gathering AS conf_gat
        INNER JOIN iteach_grades_quantitatives.evaluation_plan AS evp ON conf_gat.id_evaluation_plan = evp.id_evaluation_plan
        WHERE evp.id_evaluation_plan = $id_evaluation_plan AND evp.gathering = 1
        ");
                $getConfGathering = $this->conn->query($smtCOGTH);

                while ($confggath = $getConfGathering->fetch(PDO::FETCH_OBJ)) {

                    $id_conf_grade_gathering = $confggath->id_conf_grade_gathering;

                    $count_GATH = $this->conn->query("SELECT COUNT(*)
                    FROM iteach_grades_quantitatives.grades_evaluation_criteria AS gec
                                INNER JOIN iteach_grades_quantitatives.conf_grade_gathering AS conf_gg ON gec.id_evaluation_plan = conf_gg.id_evaluation_plan
                                INNER JOIN iteach_grades_quantitatives.grade_gathering AS gg ON conf_gg.id_conf_grade_gathering = gg.id_conf_grade_gathering
                                INNER JOIN iteach_grades_quantitatives.final_grades_assignment fga ON gg.id_final_grade = fga.id_final_grade
                                WHERE gg.id_conf_grade_gathering = $id_conf_grade_gathering AND fga.id_student = $id_student AND gec.id_grades_evaluation_criteria = gg.id_grades_evaluation_criteria")->fetchColumn();

                    if ($count_GATH == 0) {

                        //--- PROCESO PARA VERIFICAR SI HAY QUE MOSTRAR ALGUNA MATERIA ADICIONAL ---//
                        $queryGRGTH = "SELECT conf_gg.id_conf_grade_gathering, conf_gg.id_evaluation_plan, gec.id_final_grade, gec.id_grades_evaluation_criteria
                FROM iteach_grades_quantitatives.grades_evaluation_criteria AS gec
                INNER JOIN iteach_grades_quantitatives.conf_grade_gathering AS conf_gg ON gec.id_evaluation_plan = conf_gg.id_evaluation_plan
                INNER JOIN iteach_grades_quantitatives.final_grades_assignment fga ON gec.id_final_grade = fga.id_final_grade
                WHERE conf_gg.id_conf_grade_gathering = $id_conf_grade_gathering AND fga.id_student = $id_student";

                        $getGraGthStructure = $this->conn->query($queryGRGTH);

                        while ($grath = $getGraGthStructure->fetch(PDO::FETCH_OBJ)) {


                            $id_conf_grade_gathering = $grath->id_conf_grade_gathering;
                            $id_evaluation_plan      = $grath->id_evaluation_plan;
                            $id_final_grade          = $grath->id_final_grade;
                            $id_grades_evaluation_criteria = $grath->id_grades_evaluation_criteria;

                            $stmtGraGth = ("INSERT INTO iteach_grades_quantitatives.grade_gathering (id_conf_grade_gathering, id_evaluation_plan, id_grades_evaluation_criteria, id_final_grade) VALUES ('$id_conf_grade_gathering', '$id_evaluation_plan', '$id_grades_evaluation_criteria', '$id_final_grade')");
                            $this->conn->query($stmtGraGth);
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
            $this->conn->query($stmtGraGth);
            $data = array(
                'response' => false,
                'message' => 'Se actualizó la hoja de trabajo'
            );
        }

        //  echo json_encode($data);
    }

    function segundos_tiempo($segundos)
    {
        $minutos = $segundos / 60;
        $horas = floor($minutos / 60);
        $minutos2 = $minutos % 60;
        $segundos_2 = $segundos % 60 % 60 % 60;
        if ($minutos2 < 10)
            $minutos2 = '0' . $minutos2;

        if ($segundos_2 < 10)
            $segundos_2 = '0' . $segundos_2;

        if ($segundos < 60) { /* segundos */
            $resultado = round($segundos) . ' Segundos';
        } elseif ($segundos > 60 && $segundos < 3600) { /* minutos */
            $resultado = $minutos2
                . ':'
                . $segundos_2
                . ' Minutos';
        } else { /* horas */
            $resultado = $horas . ':' . $minutos2 . ':' . $segundos_2 . ' Horas';
        }
        return $resultado;
    }

    public function sendMailStructure($html, $time_txt)
    {
echo "hereeee";
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'notificacionykt@ae.edu.mx';                     //SMTP username
            $mail->Password   = 'Ykt2020a';                               //SMTP password
            $mail->SMTPSecure = "";            //Enable implicit TLS encryption
            $mail->Port       = 25;

            $mail->SMTPDebug = false;

            //Recipients
            $mail->setFrom('no-contestar@ae.edu.mx', utf8_decode('iTEACH TOOLS'));                               //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->addAddress('antoniogonzalez.rt@gmail.com');
            $mail->addAddress('i.sistemas@ae.edu.mx');
            //$mail->addAddress('i.sistemas@ae.edu.mx');
            //$mail->addAddress('antoniogonzalez.rt@gmail.com');
            //$mail->addAddress('i.sistemas@ae.edu.mx');

            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = utf8_decode('Actualización iTeach Tools' . $time_txt);
            $mail->Body    = $html;

            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content

            $mail->send();
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function sendMailBeginProccess($html)
    {

        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'notificacionykt@ae.edu.mx';                     //SMTP username
            $mail->Password   = 'Ykt2020a';                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = 465;

            $mail->SMTPDebug = false;

            //Recipients
            $mail->setFrom('no-contestar@ae.edu.mx', utf8_decode('iTEACH TOOLS'));                               //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->addAddress('antoniogonzalez.rt@gmail.com');
            $mail->addAddress('i.sistemas@ae.edu.mx');
            //$mail->addAddress('antoniogonzalez.rt@gmail.com');
            //$mail->addAddress('i.sistemas@ae.edu.mx');

            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = utf8_decode('Inicio de proceso');
            $mail->Body    = $html;

            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content

            $mail->send();
        } catch (Exception $e) {
        }
    }
}
