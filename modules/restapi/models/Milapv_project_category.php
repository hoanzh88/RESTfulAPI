<?php defined('BASEPATH') OR exit('No direct script access allowed');



class Milapv_project_category extends CI_Model {

    

    public function __construct()	{

        $this->load->database();

    }

            

    public function get($levelCode)

    {

        $this->db->select('c.id AS assessmentCategoryId, c.name, l.programmeLevelId, lv.levelCode, l.module, l.nameEN, l.nameVN, l.score')

        ->from('mdl_ila2_project_assessment_list AS l')

        ->join('mdl_ila2_project_assessment_category AS c','l.assessmentCategoryId = c.id')

        ->join('mdl_ila_programme_level_name AS lv','lv.id = l.programmeLevelId')        

        ->where('c.type = 2 AND lv.levelCode = "'.$levelCode.'"')

        ->order_by('l.score DESC');

       // echo $this->db->get_compiled_select();

        return $this->db->get()->result();

    }

    public function getMathCategory($classId)
	{

        $this->db->select('classId, name, lessonNo, testDate, grammarLabel, grammarWeight, skill, weight, coursePoint, lessonDescription, lessonName, groupTopic')

        ->from('mdl_ila_class_offline_tests')

        ->where('classId', $classId)

        ->order_by('lessonNo ASC'); 

        $testData = $this->db->get()->result();

        $assessmentData = array();

        if ($testData){

            foreach ($testData as $key => $item) {

                $_name = $item->name;

                if ($_name == 'Formative') {

                    $_lessonNo = $item->lessonNo;

                    $_skill = $item->skill;

                    $_grammarLabel = $item->grammarLabel;

                    $_grammartLabelKey = strtolower($_grammarLabel);
    
                    $_groupTopic = $item->groupTopic;
    
                    $_lessonName = $item->lessonName;

                    if ($_skill == 'Soft') {

                        $assessmentData[$_grammarLabel] = $this->softAssessmentList($_grammarLabel);

                    }

                } else {

                    return null;

                }

            }

            // $data = (array)$assessmentData;

        }

        return $assessmentData;

    }

    private function softAssessmentList($name)
	{

        $this->db->select('al.id AS softScore, al.nameEN, al.score, al.assessmentCategoryId, ac.name, ac.order, ac.id')

        ->from('mdl_ila3_maths_softskill_assessment_category ac')

        ->join('mdl_ila3_maths_softskill_assessment_list al','al.assessmentCategoryId = ac.id','left')
        
        ->where('ac.name', $name);

        $testData = $this->db->get()->result();

        $assessmentData = array();

        $assessmentList = array();

        $assessmentTotal = array();

        if ($testData) {

            foreach ($testData as $key => $item) {

                $_name = $item->name;

                $_nameEN = $item->nameEN;

                $_assessmentCategoryId = $item->assessmentCategoryId;

                $assessmentData[] = array(

                    'id' => $item->softScore,

                    'name' => $_name,

                    'assessmentCategoryId' => $_assessmentCategoryId,

                    'nameEN' => $item->nameEN,

                    // 'nameVN' => $item->nameVN,

                    'score' => $item->score,

                );

            }

        }

        return $assessmentData;

    }

    

    /* private function getProgramme($levelCode){

        $this->db->select()

        ->from('mdl_ila_programme_level_name')

        ->where('levelCode = "'.$levelCode.'"');

        return $this->db->get()->row();

    } */

    

}