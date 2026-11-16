
package handlers

import (
	"database/sql"
	"encoding/json"
	"log"
	"net/http"

	"student-dormitory-management/backend/internal/models"

	"golang.org/x/crypto/bcrypt"
)

// ListStudents - гирифтани рӯйхати ҳамаи донишҷӯён
func ListStudents(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodGet {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
			return
		}

		query := `
			SELECT
				s.id,
				s.user_id,
				u.full_name,
				u.username,
				u.email,
				u.phone,
				s.student_number,
				s.faculty,
				s.study_group,
				s.course,
				s.birth_date
			FROM students s
			INNER JOIN users u ON s.user_id = u.id
			ORDER BY s.id DESC
		`

		rows, err := db.QueryContext(r.Context(), query)
		if err != nil {
			log.Println("Error fetching students:", err)
			http.Error(w, "Failed to fetch students", http.StatusInternalServerError)
			return
		}
		defer rows.Close()

		students := make([]models.Student, 0)

		for rows.Next() {
			var student models.Student
			var email sql.NullString
			var phone sql.NullString
			var birthDate sql.NullTime

			err := rows.Scan(
				&student.ID,
				&student.UserID,
				&student.FullName,
				&student.Username,
				&email,
				&phone,
				&student.StudentNumber,
				&student.Faculty,
				&student.StudyGroup,
				&student.Course,
				&birthDate,
			)

			if err != nil {
				log.Println("Error scanning student:", err)
				http.Error(w, "Failed to read student data", http.StatusInternalServerError)
				return
			}

			if email.Valid {
				student.Email = &email.String
			}

			if phone.Valid {
				student.Phone = &phone.String
			}

			if birthDate.Valid {
				student.BirthDate = &birthDate.Time
			}

			students = append(students, student)
		}

		if err := rows.Err(); err != nil {
			log.Println("Error iterating students:", err)
			http.Error(w, "Failed to read students", http.StatusInternalServerError)
			return
		}

		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.WriteHeader(http.StatusOK)

		if err := json.NewEncoder(w).Encode(students); err != nil {
			log.Println("Error encoding students:", err)
		}
	}
}

// CreateStudent - илова кардани донишҷӯи нав
func CreateStudent(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
			return
		}

		var req models.CreateStudentRequest

		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			http.Error(w, "Invalid JSON data", http.StatusBadRequest)
			return
		}

		if req.FullName == "" ||
			req.Username == "" ||
			req.Password == "" ||
			req.StudentNumber == "" ||
			req.Faculty == "" ||
			req.StudyGroup == "" ||
			req.Course < 1 || req.Course > 6 {
			http.Error(w, "Required fields are missing or invalid", http.StatusBadRequest)
			return
		}

		passwordHash, err := bcrypt.GenerateFromPassword(
			[]byte(req.Password),
			bcrypt.DefaultCost,
		)
		if err != nil {
			http.Error(w, "Password processing failed", http.StatusInternalServerError)
			return
		}

		tx, err := db.BeginTx(r.Context(), nil)
		if err != nil {
			http.Error(w, "Database transaction failed", http.StatusInternalServerError)
			return
		}
		defer tx.Rollback()

		result, err := tx.ExecContext(r.Context(), `
			INSERT INTO users
			(full_name, username, email, password_hash, role, phone)
			VALUES (?, ?, NULLIF(?, ''), ?, 'student', NULLIF(?, ''))
		`,
			req.FullName,
			req.Username,
			req.Email,
			string(passwordHash),
			req.Phone,
		)
		if err != nil {
			log.Println("Error creating user:", err)
			http.Error(w, "Failed to create user. Username or email may already exist.", http.StatusConflict)
			return
		}

		userID, err := result.LastInsertId()
		if err != nil {
			http.Error(w, "Failed to get user ID", http.StatusInternalServerError)
			return
		}

		result, err = tx.ExecContext(r.Context(), `
			INSERT INTO students
			(user_id, student_number, faculty, study_group, course, birth_date)
			VALUES (?, ?, ?, ?, ?, NULLIF(?, ''))
		`,
			userID,
			req.StudentNumber,
			req.Faculty,
			req.StudyGroup,
			req.Course,
			req.BirthDate,
		)
		if err != nil {
			log.Println("Error creating student:", err)
			http.Error(w, "Failed to create student. Student number may already exist.", http.StatusConflict)
			return
		}

		studentID, err := result.LastInsertId()
		if err != nil {
			http.Error(w, "Failed to get student ID", http.StatusInternalServerError)
			return
		}

		if err := tx.Commit(); err != nil {
			http.Error(w, "Failed to save student", http.StatusInternalServerError)
			return
		}

		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.WriteHeader(http.StatusCreated)

		if err := json.NewEncoder(w).Encode(map[string]interface{}{
			"message":    "Student created successfully",
			"student_id": studentID,
			"user_id":    userID,
		}); err != nil {
			log.Println("Error encoding response:", err)
		}
	}
}

// GetStudentByID - гирифтани як донишҷӯ аз рӯйи ID
func GetStudentByID(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodGet {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
			return
		}

		id := r.PathValue("id")
		if id == "" {
			http.Error(w, "Student ID is required", http.StatusBadRequest)
			return
		}

		query := `
			SELECT
				s.id,
				s.user_id,
				u.full_name,
				u.username,
				u.email,
				u.phone,
				s.student_number,
				s.faculty,
				s.study_group,
				s.course,
				s.birth_date
			FROM students s
			INNER JOIN users u ON s.user_id = u.id
			WHERE s.id = ?
		`

		var student models.Student
		var email sql.NullString
		var phone sql.NullString
		var birthDate sql.NullTime

		err := db.QueryRowContext(r.Context(), query, id).Scan(
			&student.ID,
			&student.UserID,
			&student.FullName,
			&student.Username,
			&email,
			&phone,
			&student.StudentNumber,
			&student.Faculty,
			&student.StudyGroup,
			&student.Course,
			&birthDate,
		)

		if err != nil {
			if err == sql.ErrNoRows {
				http.Error(w, "Student not found", http.StatusNotFound)
				return
			}

			log.Println("Error fetching student:", err)
			http.Error(w, "Failed to fetch student", http.StatusInternalServerError)
			return
		}

		if email.Valid {
			student.Email = &email.String
		}

		if phone.Valid {
			student.Phone = &phone.String
		}

		if birthDate.Valid {
			student.BirthDate = &birthDate.Time
		}

		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.WriteHeader(http.StatusOK)

		if err := json.NewEncoder(w).Encode(student); err != nil {
			log.Println("Error encoding student:", err)
		}
	}
}
