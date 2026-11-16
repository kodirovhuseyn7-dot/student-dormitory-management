
package routes

import (
	"database/sql"
	"net/http"

	"student-dormitory-management/backend/internal/handlers"
)

func RegisterStudentRoutes(mux *http.ServeMux, db *sql.DB) {
	// Рӯйхати донишҷӯён ва илова кардани донишҷӯи нав
	mux.HandleFunc("/api/students", func(w http.ResponseWriter, r *http.Request) {
		switch r.Method {
		case http.MethodGet:
			handlers.ListStudents(db)(w, r)

		case http.MethodPost:
			handlers.CreateStudent(db)(w, r)

		default:
			w.Header().Set("Allow", "GET, POST")
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	// Гирифтани як донишҷӯ аз рӯйи ID
	mux.HandleFunc("GET /api/students/{id}", handlers.GetStudentByID(db))
}
