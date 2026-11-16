
package models

import "time"

type Student struct {
	ID            uint64     `json:"id"`
	UserID        uint64     `json:"user_id"`
	FullName      string     `json:"full_name"`
	Username      string     `json:"username"`
	Email         *string    `json:"email"`
	Phone         *string    `json:"phone"`
	StudentNumber string     `json:"student_number"`
	Faculty       string     `json:"faculty"`
	StudyGroup    string     `json:"study_group"`
	Course        uint8      `json:"course"`
	BirthDate     *time.Time `json:"birth_date"`
}
