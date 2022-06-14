<?php defined('BASEPATH') OR exit('No direct script access allowed');



class Milapv_student extends CI_Model {

    

    public function __construct()   {

        $this->load->database();

    }

        

    public function getById($id)

    {

        $this->db->select();

        $this->db->where('id', $id);

        return $this->db->get($this->_table)->row();

    }

    

    public function getStudents($username)

    {

        if (! $username) return null;

        $this->db->select('userId, studentCode, firstName, middleName, lastName, picture as avatar')

        ->from('ilaweb_tbluserstudent')

        ->where('username = "'.$username.'"')

        ->group_by('studentCode, username');

        return $this->db->get()->result();

    }

    

    public function getTopClassByStudent($studentCode)

    {

        /*

         * Note: Please index column studentCode

         *      ALTER TABLE `mdl_ila_class_student` ADD INDEX(`studentCode`);

         * */

        $this->db->select('c.id, c.classCode, cs.studentCode, cs.registrationId, c.className, c.classGroup, c.classStatus, c.programLevel, c.level, cs.classId, cs.studentId, c.reminderId, c.albumId, cs.actualStartDate, cs.actualEndDate')

        ->from('mdl_ila_class_student AS cs')

        ->join('mdl_ila_class AS c','c.id = cs.classId')

        ->where('cs.registrationStatus = 2 AND terminationStatus = 0

        AND (LOWER(c.classGroup) LIKE "%public%" OR LOWER(c.classGroup) ="summer" OR c.programmeLevelId=5)

        AND studentCode = "'.$studentCode.'"

        AND (classStatus = 3 OR classStatus = 4)')

        ->order_by('classId DESC')

        ->limit(2);

        //echo $this->db->get_compiled_select(); die;

        $classes = $this->db->get()->result();

        return $classes;

    }

    

    public function getLastClassByStudentCode($studentCodeList)

    {

        $str = preg_replace('/\'/i', '', $studentCodeList);

        $str = preg_replace('/\(/i', '', $str);

        $str = preg_replace('/\)/i', '', $str);

        $students = explode(',',$str);

        //p($students);

        

        $output = array();

        foreach ($students as $studentCode){

            $studentClasses = $this->getTopClassByStudent($studentCode);

            if ($studentClasses){

                // Find Ongoing class

                /*$ongoingArr = array();

                $finishArr = array();

                foreach ($studentClasses as $k => $c){

                    if ($c->classStatus == 3){

                        $ongoingArr[] = $c;

                    }else{

                        $finishArr[] = $c;

                    }

                }

                

                if (count($ongoingArr) > 0){

                    $output = array_merge($ongoingArr, $output);

                }else{

                    $output = array_merge(array($finishArr[0]), $output);

                }*/

				$output = array_merge($studentClasses, $output);

            }

        }

        //p($output);

        //die;

        return $output;

    }



    public function getListSurveys($classCodes, $studentCodes){

        /*

         *  SELECT c.id, c.classCode, c.className, st.firstName, CONCAT(st.lastName," ", st.middleName, " ", st.firstName) AS fullname, s.studentCode, cs.date AS surveyDate  

         *  FROM mdl_ila_course_schedule cs 

            JOIN mdl_ila_class c ON c.id = cs.classId

            JOIN mdl_ila_class_student s ON s.classId = cs.classId

            JOIN mdl_ila_student st ON st.ssStudentCode = s.studentCode

            JOIN ilapv_survey_link sl On sl.id = cs.surveyId

            WHERE c.classCode IN ("H3YJ-4B-1606","H3YJ-5B-1704","N1YK-L4-1702")

            AND s.studentCode IN ("H0076246","H0116101")

            AND c.classType = 1 AND (c.classStatus = 3 OR c.classStatus = 4)

            AND (cs.surveyId IS NOT NULL OR cs.surveyId > 0)

            AND DATEDIFF(s.actualStartDate, cs.date) < 0 

            AND DATEDIFF(s.actualEndDate, cs.date) > 0 

         * */

        // c.classType = 1 : EY

        $this->db->select('c.id, sl.id AS surveyId, c.classCode, c.className, st.firstName, CONCAT(st.lastName," ", st.middleName, " ", st.firstName) AS fullname, s.studentCode, cs.date AS surveyDate, sl.surveyLink')

        ->from('mdl_ila_course_schedule cs')

        ->join('mdl_ila_class c','c.id = cs.classId')

        ->join('mdl_ila_class_student s','s.classId = cs.classId')

        ->join('mdl_ila_student st','st.ssStudentCode = s.studentCode')

        ->join('ilapv_survey_link sl','sl.id = cs.surveyId')

        ->where('c.classCode IN '.$classCodes.' AND s.studentCode IN '.$studentCodes.' 

                AND c.classType = 1 

                AND (c.classStatus = 3 OR c.classStatus = 4) 

                AND (cs.surveyId IS NOT NULL OR cs.surveyId > 0)

                AND DATEDIFF(s.actualStartDate, cs.date) < 0 

                AND DATEDIFF(s.actualEndDate, cs.date) > 0

				AND DATEDIFF(NOW(), cs.date) >= 0

                AND sl.isActive = 1');

        //echo $this->db->get_compiled_select(); die;

        return $this->db->get()->result();

        

    }

    

    public function checkSurveyDone($username, $classCode, $studentCode){

        $this->db->select('id')

        ->where(

            array(

                'classCode' => $classCode,

                'studentCode' => $studentCode,

                'username' => $username            

            )

        );

        //echo $this->db->get_compiled_select(); die;

        $result = $this->db->get('ilapv_survey_done')->result();

        if ($result){

            return true;

        }

        return false;

    }

    

    //$this->db->affected_rows() == 0 // Update query was ran successfully but nothing was updated

    //$this->db->affected_rows() == 1 // Update query was ran successfully and 1 row was updated

    //$this->db->affected_rows() == -1 // Query failed

    

}

