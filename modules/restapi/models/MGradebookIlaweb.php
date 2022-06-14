<?php defined('BASEPATH') OR exit('No direct script access allowed');



class MGradebookIlaweb extends CI_Model {

    

    public function __construct()	{

        $this->load->database();

    }    

    

    public function getHistoryClasses($studentCode, $moodleClassesCodes=''){

        $filter = $moodleClassesCodes ? ' AND classCode NOT IN '.$moodleClassesCodes : '';

        $this->db->select('f.finalScore, c.classId, Programme AS className, classCode, c.studentCode, registrationId, LEFT(startdate, 10) AS startdate, LEFT(enddate, 10) AS enddate, classStatus, c.passed, "web" AS source')

        ->distinct()   

        ->from('ilaweb_vr_study_overview c')

        ->join('ilaweb_vr_assessment_result_non_ospp_final AS f','f.studentCode = c.studentCode AND f.classID = c.classId')

        ->where('c.studentCode = "'.$studentCode.'" AND LOWER(classStatus) = "finished" '.$filter)

        ->order_by('enddate DESC');

        return $this->db->get()->result();

    }



    public function getHistoryClassDetails($studentCode, $classCode){

        $output = array(

            'source' => 'web',

            'attendance' => null,

            'assessment' => null,

            'teacherComments' => null,

        );

        /* $studentCode = 'H0116101';

        $classCode = 'H3YJ-1A-1404'; */        

        $output['teacherComments'] = $this->getTeacherComments($studentCode, $classCode);

        $output['attendance'] = $this->getAttendance($studentCode, $classCode);

        $output['assessment'] = $this->getAssessment($studentCode, $classCode);

        return $output;

    }

    

    public function getAttendance($studentCode, $classCode){

        $this->db->select('NoOfClass AS total, NoAbsent AS absent, NoAttended AS attend, AbsentDay AS absentDates')

        ->from('ilaweb_vr_or_studentattendance')

        ->where('studentCode = "'.$studentCode.'" AND classCode = "'.$classCode.'"');

        $attendance = $this->db->get()->row();

        if ($attendance){

            // Prepare attendance struct

            $absentDateArr = explode(',',$attendance->absentDates);

            if ($absentDateArr){

                $attendance->absentDates = array();

                foreach ($absentDateArr as $date){

                    $attendance->absentDates[] = array('date'=> $date, 'reason'=>'');

                }

            }

        }

        return $attendance;

    }

    

    public function getAssessment($studentCode, $classCode){

        $this->db->select('mark AS score, weight, LEFT(testDate,10) AS testDate, typeOfTest, descrEn, descr, descrVn, sort')

        ->distinct()

        ->from('ilaweb_vr_assessment_result_non_ospp')

        ->where('studentCode = "'.$studentCode.'" AND classCode = "'.$classCode.'"')

        ->order_by('sort, testDate, descrEn');

        $assessment = $this->db->get()->result();

        

        $output = array();

        foreach ($assessment as $item){

            $type = $item->typeOfTest;

            if (! isset($output[$type])){

                $output[$type] = array(

                    'type' => $item->descr,

                    'listTests' => array()

                );

            }

            $output[$type]['listTests'][] = $item;

        }

        return $output;        

    }

    

    public function getTeacherComments($studentCode, $classCode){

        $this->db->select()

        ->from('ilaweb_vr_teachercomments')

        ->where('studentCode = "'.$studentCode.'" AND classCode = "'.$classCode.'"')

        ->order_by('CommentDate DESC');

        $teacherComments = $this->db->get()->result();

        

        $mapLabel = array(

            'Overall' => 'Overall performance and progress',

            'Speaking' => 'Speaking',

            'Writing' => 'Writing',

            'Pronunciation' => 'Grammar and vocabulary',

            'Grammar' => 'Grammar and vocabulary',

            'Listening' => 'Listening',

            'Reading' => 'Reading',

            'Participation' => 'Participation'

        );

        

        $output = array();

        foreach ($teacherComments as $item){

            $date = substr($item->CommentDate,0,10);

            $map = array(

                'Overall' => '',

                'Speaking' => '',

                'Pronunciation' => '',

                'Writing' => '',

                'Grammar' => '',

                'Listening' => '',

                'Reading' => '',

                'Participation' => '',

            );

            if (! isset($output[$date])){

                $output[$date] = array();

            }



            foreach ($map as $_col => $val){

                $colVN = $_col.'VN';

                if ($item->$_col){

                    $row = array(

                        'date' => $date,

                        'name' => $mapLabel[$_col],

                        'nameEN' => $item->$_col,

                        'nameVN' => $item->$colVN,

                    );

                    $output[$date][] = $row;

                }

            }            

        }

        return $output;

    }

    

    //$this->db->affected_rows() == 0 // Update query was ran successfully but nothing was updated

    //$this->db->affected_rows() == 1 // Update query was ran successfully and 1 row was updated

    //$this->db->affected_rows() == -1 // Query failed

    

}