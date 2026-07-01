<?php

namespace App\Support;

use App\Models\Student;

class StudentSnapshotBuilder
{
    public static function build(Student $student): array
    {
        $educationRows = $student->educationDetails
            ? $student->educationDetails->map(fn($e) => [
                'exam_name'        => $e->exam_name,
                'institute_name'   => $e->institute_name,
                'education_stream' => $e->education_stream,
                'board_university' => $e->board_university,
                'roll_number'      => $e->roll_number,
                'passing_year'     => $e->passing_year,
                'district'         => $e->district,
                'division'         => $e->division,
                'obtained_marks'   => $e->obtained_marks,
                'max_marks'        => $e->max_marks,
                'percentage'       => $e->percentage,
            ])->values()->all()
            : [];

        return [
            // Personal
            'name'              => $student->name,
            'mobile'            => $student->mobile,
            'email'             => $student->email,
            'dob'               => $student->dob?->format('Y-m-d'),
            'gender'            => $student->gender,
            'category'          => $student->category,
            'special_category'  => $student->special_category,
            'nationality'       => $student->nationality,
            'religion'          => $student->religion,
            'student_type'      => $student->student_type,
            'aadhar_no'         => $student->aadhar_no,
            'apaar_no'          => $student->apaar_no,
            'marital_status'    => $student->marital_status,
            'photo'             => $student->photo,
            // Family
            'father_name'       => $student->father_name,
            'father_mobile'     => $student->father_mobile,
            'father_occupation' => $student->father_occupation,
            'mother_name'       => $student->mother_name,
            'mother_mobile'     => $student->mother_mobile,
            'mother_occupation' => $student->mother_occupation,
            'guardian_name'     => $student->guardian_name,
            'guardian_mobile'   => $student->guardian_mobile,
            'guardian_relation' => $student->guardian_relation,
            // Permanent address
            'perm_village'      => $student->perm_village,
            'perm_post'         => $student->perm_post,
            'perm_thana'        => $student->perm_thana,
            'perm_district'     => $student->perm_district,
            'perm_state'        => $student->perm_state,
            'perm_pincode'      => $student->perm_pincode,
            'perm_address'      => $student->perm_address,
            // Communication address
            'comm_same_as_perm' => (bool) $student->comm_same_as_perm,
            'comm_city'         => $student->comm_city,
            'comm_post'         => $student->comm_post,
            'comm_thana'        => $student->comm_thana,
            'comm_district'     => $student->comm_district,
            'comm_state'        => $student->comm_state,
            'comm_pincode'      => $student->comm_pincode,
            'comm_address'      => $student->comm_address,
            // Scholarship
            'has_scholarship'            => (bool) $student->has_scholarship,
            'scholarship_name'           => $student->scholarship_name,
            'scholarship_type'           => $student->scholarship_type,
            'scholarship_authority'      => $student->scholarship_authority,
            'scholarship_ref_no'         => $student->scholarship_ref_no,
            'scholarship_amount'         => $student->scholarship_amount,
            'scholarship_applied_date'   => $student->scholarship_applied_date?->format('Y-m-d'),
            // Education
            'education'         => $educationRows,
        ];
    }
}
