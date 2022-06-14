<?php defined('BASEPATH') OR exit('No direct script access allowed');



class MGradebookMoodle extends CI_Model {

    

    public function __construct()	{

        $this->load->database();

    }   

    

    public function getHistoryClasses($studentCode){

        $moodleClasses = $this->getFinishedClasses($studentCode);

        $classIds = buildSqlList($moodleClasses, 'classId');

        $scores = $this->getScores($studentCode, $classIds);

        

        // Adding final score into $moodleClasses

        foreach ($moodleClasses as $key => $class){

            $_classId = $class->classId;

            $_registrationId = $class->registrationId;

            if (isset($scores[$_classId][$_registrationId])){

                $moodleClasses[$key]->finalScore = $scores[$_classId][$_registrationId]['finalScore'];

                $moodleClasses[$key]->passed = $scores[$_classId][$_registrationId]['pass'];

            }

        }

        return $moodleClasses;        

        /* Output: Class Object format:

             stdClass Object

             (

                 [classId] => 690

                 [className] => a Super Juniors Course

                 [classCode] => H3YJ-4B-1505

                 [studentCode] => H0076246

                 [registrationId] => 6850

                 [startdate] => 2015-05-10

                 [enddate] => 2015-09-13

                 [classStatus] => 4

                 [source] => moodle

                 [finalScore] => 90.67

                 [passed] => 1

             )

         */

    }

        

    public function getHistoryClassDetails($studentCode, $classCode){

        $output = array(

            'source' => 'mod',

            'attendance' => null,

            'assessment' => null,

            'teacherComments' => null,

        );        

       

        $regInfo = $this->getRegistrationInfo($studentCode, $classCode);

        if ($regInfo){

            $classId = $regInfo->classId;

            $registrationId = $regInfo->registrationId;

            

            $output['attendance'] = $this->getAttendance($classId, $registrationId);

            $output['assessment'] = $this->getAssessment($classId, $registrationId);

            $output['teacherComments'] = $this->getTeacherComments($registrationId);

        }

        return $output;

    }

    

    private function getFinishedClasses($studentCode){

        $this->db->select('c.id AS classId, c.programLevel, c.level, c.className, c.classCode, cs.studentCode, cs.registrationId, cs.actualStartDate AS startdate, cs.actualEndDate AS enddate, c.classStatus, DATEDIFF(NOW(),cs.actualEndDate) AS datediff, "mod" AS source ')

        ->from('mdl_ila_class_student AS cs')

        ->join('mdl_ila_class AS c','c.id = cs.classId')

        ->where(array(

            'cs.studentCode' => $studentCode,

            'cs.registrationStatus' => 2,

            'cs.terminationStatus' => 0

        ))

        ->where('c.classStatus = 4')

        ->where('DATEDIFF(NOW(), cs.actualEndDate) > 0')

        ->order_by('cs.actualEndDate DESC')

        ->limit(200,0);

        /* echo $this->db->get_compiled_select();

        die; */

        return $this->db->get()->result();

    }

    

    private function getScores($studentCode, $classIds){

        

        $this->db->select('cs.classId, cs.registrationId, CONCAT(s.lastName, " ",s.middleName, " ", s.firstName) AS fullname, c.classCode, p.name AS programme, c.level')

        ->from('mdl_ila_class_student AS cs')

        ->join('mdl_ila_student AS s','s.id = cs.studentId')

        ->join('mdl_ila_class AS c','c.id = cs.classId')

        ->join('mdl_ila_programme_level AS p','c.programmeLevelId = p.id')

        ->where('classId IN '.$classIds .' AND cs.studentCode = "'.$studentCode.'" AND registrationStatus = 2 AND terminationStatus = 0')

        ->order_by('s.firstName ASC');

        $students = $this->db->get()->result();

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

    

    

    private function getRegistrationInfo($studentCode, $classCode)

    {

        /*

         SELECT * FROM `mdl_ila_class_student` cs

         JOIN mdl_ila_class c ON c.id = cs.classId

         WHERE `studentCode` = 'H0116101' AND c.classCode = 'H3YJ-5A-1701'

         * */

        $this->db->select('cs.registrationId, cs.classId, cs.studentId')

        ->from('mdl_ila_class_student AS cs')

        ->join('mdl_ila_class AS c','c.id = cs.classId')

        ->where(array(

            'studentCode' => $studentCode,

            'c.classCode' => $classCode

        ));

        return $this->db->get()->row();

    }

    

    private function getAssessment($classId, $registrationId)

    {

        $this->db->select('id AS oflinetestsId, classId, name, lessonNo, testDate, grammarLabel, grammarWeight AS maxScore, weight')

        ->from('mdl_ila_class_offline_tests')

        ->where('classId', $classId)

        ->order_by('testDate ASC');

        $classOfflineTests = $this->db->get()->result_array();

        

        $this->db->select('oflinetestsId, registrationId, grammarScore')

        ->from('mdl_ila_student_offline_test_score')

        ->where('registrationId', $registrationId);

        $studentOfflineScores = $this->db->get()->result();

        

        $scoreArr = array();

        if ($studentOfflineScores){

            foreach ($studentOfflineScores as $key => $item) {

                $scoreArr[$item->oflinetestsId] = $item->grammarScore;

            }

        }

        

        $data = array();

        if ($classOfflineTests){

            

            foreach ($classOfflineTests as $key => $item) {

                $moduleKey = trim(strtolower($item['name']));

                $moduleKey = preg_replace('/\-/', ' ',$moduleKey);

                $moduleKey = preg_replace('/\s+/', '_',$moduleKey);

                if (strpos($moduleKey, 'cambridge') !== false) {

                    $moduleKey = 'cambridge';

                }

                $_oflinetestsId = $item['oflinetestsId'];

                

                $data[$_oflinetestsId] = $item;

                $data[$_oflinetestsId]['score'] = isset($scoreArr[$_oflinetestsId]) ? $scoreArr[$_oflinetestsId] : 0;

                $data[$_oflinetestsId]['type'] = $this->checkAssessmentType($moduleKey);

                $data[$_oflinetestsId]['mdl_oflinetest_Id'] = $_oflinetestsId;

                

            }

        }

        //p($data);

        return $data; 	    

        

    }

    

    private function getTeacherComments($registrationId)

    {

        /* SELECT cc.commentDate, cat.name, lc.nameEN, lc.nameVN, lc.score

        FROM `mdl_ila_class_online_teacher_comment` cc

        JOIN mdl_ila_student_online_teacher_comment sc ON sc.onlineCommentId = cc.id

        JOIN mdl_ila_online_comment_category cat ON cat.id = sc.categoryId

        JOIN mdl_ila_online_comment_list lc ON lc.id = sc.onlineCommentListId

        WHERE `classId` = 16032 AND sc.registrationId = 215776

        * */

        /* $sql = 'SELECT cc.commentDate, cat.name, lc.nameEN, lc.nameVN, lc.score

                FROM `mdl_ila_class_online_teacher_comment` cc

                JOIN mdl_ila_student_online_teacher_comment sc ON sc.onlineCommentId = cc.id

                JOIN mdl_ila_online_comment_category cat ON cat.id = sc.categoryId

                JOIN mdl_ila_online_comment_list lc ON lc.id = sc.onlineCommentListId

                WHERE `classId` = '.$classId.' AND sc.registrationId = '.$registrationId;

        return $this->db->result($sql); */

        

        $this->db->select('cc.commentDate, cat.name, lc.nameEN, lc.nameVN, lc.score')

        ->from('mdl_ila_class_online_teacher_comment cc')

        ->join('mdl_ila_student_online_teacher_comment sc', 'sc.onlineCommentId = cc.id')

        ->join('mdl_ila_online_comment_category cat', 'cat.id = sc.categoryId')

        ->join('mdl_ila_online_comment_list lc', 'lc.id = sc.onlineCommentListId')        

        ->where('sc.registrationId', $registrationId);

        //echo $this->db->get_compiled_select(); die;

        return $this->db->get()->result();

    }

    

    private function getAttendance($classId, $registrationId)

    {

        $this->db->select('se.lessonDate, se.lessonNo, se.classCode, sa.attendanceScore, sa.reason, se.hrsDoneTotal AS hrsDone')

        ->from('mdl_ila_schedule se')

        ->join('mdl_ila_student_attendance sa', 'sa.scheduleId = se.id','left')

        ->join('mdl_ila_class_student s', 's.studentId = sa.studentId')

        ->where(array(

            'se.classId' => $classId,

            's.registrationId' => $registrationId

        ))

        ->order_by('se.lessonDate ASC');

        

        $result = $this->db->get()->result();

        //p($result, 'getAttendance') ;

        

        $attendData = array(

            'attend' => 0,

            'absent' => 0,

            'total' => 0,

            'totalHours' => 0,

            'absentDates' => array(),

        );

        if($result)

        {

            foreach ($result as $key => $item){

                $attendScore = $item->attendanceScore ? intVal($item->attendanceScore) : 0;

                if($attendScore){

                    $attendData['attend'] ++;

                }else{

                    $attendData['absent'] ++;

                    $attendData['absentDates'][] = array('date' => $item->lessonDate, 'reason' => $item->reason);

                }

                $attendData['totalHours'] = $item->hrsDone;

                $attendData['total'] ++;

            }

        }

        return $attendData;	  

    }



    /* ------------------- START - CHECK Pass/Fail ------------------------- */

    private function checkPassFail($student){

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

    

    function checkAssessmentType($moduleKey){

        // $moduleKey format: lowercase, remove white space .., ex: end-of-project-assessment

        $typeOfTest = array(

            'P' => 'Progress Test',

            'A' => 'Achievement Test',

            'H' => 'Homework',

        );

        $maps = array(

            array(

                'typeKey' => 'P',

                'modules' => 'end_of_project_assessment,mid_module_test,end_of_module_test'

            ),

            array(

                'typeKey' => 'A',

                'modules' => 'cambridge'

            ),

            array(

                'typeKey' => 'H',

                'modules' => 'homework,supplementary_assessment'

            ),

        );

        

        $result = '';

        foreach ($maps as $m) {

            if (strpos($m['modules'], $moduleKey) !== false) {

                $result = $m['typeKey'];

                break;

            }

        }

        return $result ? $typeOfTest[$result] : '';

    }

    //$this->db->affected_rows() == 0 // Update query was ran successfully but nothing was updated

    //$this->db->affected_rows() == 1 // Update query was ran successfully and 1 row was updated

    //$this->db->affected_rows() == -1 // Query failed

    

}