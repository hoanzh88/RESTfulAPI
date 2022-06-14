<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MStudentScores extends CI_Model {

    

    public function __construct()	{

        $this->load->database();

    }

              

    public function getScores($studentCode, $classIds){

        

        $this->db->select('cs.classId, cs.registrationId, CONCAT(s.lastName, " ",s.middleName, " ", s.firstName) AS fullname, c.classCode, p.name AS programme, c.level')

        ->from('mdl_ila_class_student AS cs')

        ->join('mdl_ila_student AS s','s.id = cs.studentId')

        ->join('mdl_ila_class AS c','c.id = cs.classId')

        ->join('mdl_ila_programme_level AS p','c.programmeLevelId = p.id')

        ->where('classId IN '.$classIds .' AND cs.studentCode = "'.$studentCode.'" AND registrationStatus = 2 AND terminationStatus = 0')

        ->order_by('s.firstName ASC');

        /* echo $this->db->get_compiled_select();

        die; */

        $students = $this->db->get()->result();

        

        //p($students);

        //$this->//p($this->db->getQuery(), '$listStudents - Query');

        

        $studentArr = array();

        $listStudents = array();

        foreach ($students as $s){

            $tmp = explode('-', $s->level);

            $_level = substr($tmp[0], 1);

            $_prog     = preg_replace('/\s+/', '', strtolower($s->programme));

            if ($_prog == 'ea') $_prog = 'globalenglish';

            $listStudents[$s->classId][$s->registrationId] = array(

                'registrationId' => $s->registrationId,

                'fullname' => $s->fullname,

                'programme' => $_prog,

                'level' => $_level

            );

            $studentArr[] = $s->registrationId;

        }

        $registrationIds = buildSqlList($students, 'registrationId');

        

        /*------------- START - Get Attendance Total -------------*/

        $this->db->select('ILA_F_GetAttendanceTotalByRegistrationId(cs.registrationId) AS attendanceTotal, cs.classId, cs.registrationId')

        ->from('mdl_ila_class_student AS cs')

        ->where('cs.classId IN '.$classIds);

        $totals = $this->db->get()->result();

        $structTotalAttendance = array();

        for($i=0,$n=count($totals);$i<$n;$i++){

            $structTotalAttendance[$totals[$i]->classId][$totals[$i]->registrationId] = $totals[$i]->attendanceTotal;

        }        

        //p($structTotalAttendance);

        /*------------- END - Get Attendance Total -------------*/

        

        /*------------- START - Get list module test -------------*/

        $this->db->select('co.classId, co.id AS offlineTestId, st.registrationId, co.name, co.testDate, co.grammarWeight, co.weight, co.grammarLabel, st.grammarScore AS score ')

        ->from('mdl_ila_class_offline_tests AS co')

        ->join('mdl_ila_student_offline_test_score AS st','co.id = st.oflinetestsId','left')

        ->where('co.classId IN '.$classIds.' AND co.name NOT LIKE "%Cambridge%" AND st.registrationId IN '.$registrationIds)

        ->order_by('co.testDate DESC');

        $offlineTests = $this->db->get()->result();

        //p($offlineTests);

        /*------------- END - Get list module test -------------*/

        

        /*------------- START - Build list module Weighted -------------*/

        $this->db->select('classId, id AS offlineTestId, name, grammarWeight, SUM(weight) AS weighted')

        ->from('mdl_ila_class_offline_tests')

        ->where('classId IN '.$classIds)    // AND co.name <> "End-of-Project Assessment"

        ->group_by('classId, name')

        ->order_by('classId');

        $offlineWeighted = $this->db->get()->result();

        

        $moduleWeightedArr     = array();      // Sum of all skill weight

        foreach ($offlineWeighted as $item){

            $moduleKEY = preg_replace('/\s+/', '_', strtolower($item->name));

            $classId = $item->classId;

            if ( !isset($moduleWeightedArr[$item->classId][$moduleKEY])){

                $moduleWeightedArr[$item->classId][$moduleKEY] = 0;

            }

            $moduleWeightedArr[$item->classId][$moduleKEY] = $item->weighted;

        }

        //p($moduleWeightedArr, 'Build list module Weighted');

        /*------------- END - Build list module Weighted -------------*/

        

        /*------------- START - Build Offline + Projects Final Score -------------*/

        $offlineModuleTotalScores  = array();      // Sum of all skill scores

        $offlineModuleFinalScores = array();

        $offlineFinalScore = array();

        $listProject = array();

        $listStudentHomework = array();

        foreach ($offlineTests as $item){

            

            $moduleKEY = preg_replace('/\s+/', '_', strtolower($item->name));

            $classId = $item->classId;

            $regId = $item->registrationId;

            $grammarLabel = strtolower(preg_replace('/\s+/', '', strtolower($item->grammarLabel)));

            $weighted = number_format($moduleWeightedArr[$item->classId][$moduleKEY] / 100, 2);

            

            // Count total project in class

            if ( !isset($listProject[$classId]) ){

                $listProject[$classId] = array();

            }

            if(strpos($grammarLabel, 'project') !== false){

                if ( ! in_array($grammarLabel, $listProject[$classId] ) ){

                    $listProject[$classId][] = $grammarLabel;

                }

            }

            

            // Total

            if ( !isset($offlineModuleTotalScores[$item->classId][$regId]) ){

                $offlineModuleTotalScores[$item->classId][$regId]['fullname']   = $listStudents[$classId][$regId]['fullname'];

                $offlineModuleTotalScores[$item->classId][$regId]['programme']  = $listStudents[$classId][$regId]['programme'];

                $offlineModuleTotalScores[$item->classId][$regId]['level']      = $listStudents[$classId][$regId]['level'];

            }

            if ( ! isset($offlineModuleTotalScores[$item->classId][$regId]['scores'][$moduleKEY]) ){

                $offlineModuleTotalScores[$item->classId][$regId]['scores'][$moduleKEY] = 0;

            }

            

            $offlineModuleTotalScores[$item->classId][$regId]['scores'][$moduleKEY] += $item->score;

            

            // Final

            if ( !isset($offlineModuleFinalScores[$item->classId][$regId]) ){

                $offlineModuleFinalScores[$item->classId][$regId]['fullname']   = $listStudents[$classId][$regId]['fullname'];

                $offlineModuleFinalScores[$item->classId][$regId]['programme']  = $listStudents[$classId][$regId]['programme'];

                $offlineModuleFinalScores[$item->classId][$regId]['level']      = $listStudents[$classId][$regId]['level'];

            }

            if ( ! isset($offlineModuleFinalScores[$item->classId][$regId]['scores'][$moduleKEY])){

                $offlineModuleFinalScores[$item->classId][$regId]['scores'][$moduleKEY] = 0;

            }

            

            /* Calculate the final: FinalStudentModuleScore = $studentModuleScore * $moduleWeighted

             - Mid, End, Supplement => FinalScore = score * weight / grammarWeight

             - Project => Project weighted * score / count_project

             */

            if ( strpos($moduleKEY, 'end-of-project_assessment') !== false && count($listProject[$classId]) ){

                $offlineModuleFinalScores[$item->classId][$regId]['scores'][$moduleKEY] = $weighted * $offlineModuleTotalScores[$item->classId][$regId]['scores'][$moduleKEY] / count($listProject[$classId]);

            }else{

                $offlineModuleFinalScores[$item->classId][$regId]['scores'][$moduleKEY] += $item->score * $item->weight / $item->grammarWeight;

            }

        }

        

        //$this->p($offlineModuleTotalScores, '$offlineModuleTotalScores');

        //$this->p($offlineModuleFinalScores, '$offlineModuleFinalScores');

        //var_dump($offlineModuleFinalScores);

        

        // $offlineFinalScore

        foreach ($offlineModuleFinalScores as $classId => $students){

            foreach ($students as $regId => $student){

                $offlineFinalScore[$classId][$regId]['fullname']    = $offlineModuleFinalScores[$classId][$regId]['fullname'];

                $offlineFinalScore[$classId][$regId]['programme']   = $offlineModuleFinalScores[$classId][$regId]['programme'];

                $offlineFinalScore[$classId][$regId]['level']       = $offlineModuleFinalScores[$classId][$regId]['level'];

                

                // Add attendance

                if ( isset($structTotalAttendance[$classId][$regId])){

                    $offlineFinalScore[$classId][$regId]['attend'] = $structTotalAttendance[$classId][$regId];

                }

                

                // Sum total final score

                $offlineFinalScore[$classId][$regId]['finalScore'] = 0;

                foreach ($student['scores'] as $module => $score){

                    $offlineFinalScore[$classId][$regId]['finalScore'] += $score;

                }

                $offlineFinalScore[$classId][$regId]['finalScore'] = number_format($offlineFinalScore[$classId][$regId]['finalScore'],2);

                

                //$_student =  $offlineFinalScore[$classId][$regId]

                

                // Check Pass/Fail

                $offlineFinalScore[$classId][$regId]['pass'] = $this->checkPassFail( $offlineFinalScore[$classId][$regId] ) ;

                

                // Bonus actual pass/fail

                if ( isset($actualPassedStruct[$regId]) ){

                    $offlineFinalScore[$classId][$regId]['actual']['pass'] = $actualPassedStruct[$regId]['pass'];

                    $offlineFinalScore[$classId][$regId]['actual']['note'] = $actualPassedStruct[$regId]['note'];

                }else{

                    $offlineFinalScore[$classId][$regId]['actual']['pass'] = 0;

                    $offlineFinalScore[$classId][$regId]['actual']['note'] = '';

                }

            }

        }

        

        //p($offlineFinalScore, 'Return offline test final score');

        return $offlineFinalScore;

    }

    

    /* ------------------- START - CHECK Pass/Fail ------------------------- */

    public function checkPassFail($student){

        $pass = 0;

        if ($student['programme'] == 'globalenglish'){

            /*

             Pass: Attendance >= 60% AND Score >= 60%

             */

            if ( $student['finalScore'] >= 60 && $student['attend'] >= 60  ){

                $pass = 1;

            }

            

        }else if ($student['programme'] == 'smartteens'){

            /* YS

             - Pass:    score >= 65 AND Attendance >= 65 (End of Module Test & Attendance)

             - Manual:  score < 59  AND score < 65

             - Fail:    score < 59  (End of Module Test)

             */

            $attend = 65;

            if ( $student['finalScore'] >= 65 && $attend >= 65 ){

                $pass = 1;

            }

        }

        else if ($student['programme'] == 'superjuniors'){

            /* YJ

             - Pass: score >= 77 AND Attendance >= 65 (End of Module Test & Attendance)

             */

            $attend = 65;

            if ( $student['finalScore'] >= 77 && $attend >= 65 ){

                $pass = 1;

            }

            

        }else if ($student['programme'] == 'jumpstart'){

            /* Check:   Attendance AND Level

             + Levels: 3-5

             - Pass: attendance: >= 95%

             - Fail: (No fail)

             

             + Levels: 6-11

             - Pass: attendance: >= 75%

             - Fail: ???

             */

            if ( $student['level'] >= 1 && $student['level'] <= 2 ){

                // Levels: 1-2 :    No fail

                $pass = 1;

                

            }else if ( $student['level'] >= 3 && $student['level'] <= 5 ){

                // Levels: 3-5

                if ($student['attend'] >= 95){

                    $pass = 1;

                }

                

            }else if ( $student['level'] >= 6 && $student['level'] <= 11 ){

                // Levels: 6-11

                if ($student['finalScore'] >= 75){

                    $pass = 1;

                }

            }

            

        }else if ($student['programme'] == 'ielts'){

            /*

             Pass:

             Level: 1A >= 5

             Level: 1B >= 5.5

             Level: 2A >= 6

             Level: 2B >= 6.5

             Level: 2C >= 7

             

             Test type: End of Module Test &

             */

        }

        return $pass;

    }

    

    //$this->db->affected_rows() == 0 // Update query was ran successfully but nothing was updated

    //$this->db->affected_rows() == 1 // Update query was ran successfully and 1 row was updated

    //$this->db->affected_rows() == -1 // Query failed

    

}