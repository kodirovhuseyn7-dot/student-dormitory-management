
package models

type CreateStudentRequest struct {
	FullName      string `json:"full_name"`
	Username      string `json:"username"`
	Email         string `json:"email"`
	Phone         string `json:"phone"`
	Password      string `json:"password"`
	StudentNumber string `json:"student_number"`
	Faculty       string `json:"faculty"`
	StudyGroup    string `json:"study_group"`
	Course        uint8  `json:"course"`
	BirthDate     string `json:"birth_date"`
}
