<?php 
// Tạo một interface để các adapter không có quyền đổi tên hàm
interface IHistoryAdapter{
    public function getHistoryClasses($studentCode);
    public function getHistoryClassDetails($classCode, $studentCode);
}