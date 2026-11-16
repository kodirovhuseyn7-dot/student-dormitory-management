
package main

import (
	"fmt"
	"log"
	"net/http"

	"student-dormitory-management/backend/internal/database"
	"student-dormitory-management/backend/internal/routes"
)

func main() {
	db, err := database.Connect()
	if err != nil {
		log.Fatal("Database connection error: ", err)
	}
	defer db.Close()

	log.Println("Database connected successfully!")

	mux := http.NewServeMux()

	mux.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/" {
			http.NotFound(w, r)
			return
		}

		w.Header().Set("Content-Type", "text/plain; charset=utf-8")
		fmt.Fprintln(w, "Системаи идоракунии хобгоҳи донишҷӯён")
		fmt.Fprintln(w, "Backend бо забони Go фаъол аст!")
		fmt.Fprintln(w, "MySQL database connected successfully!")
	})

	mux.HandleFunc("/api/health", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		fmt.Fprintln(w, `{"status":"success","database":"connected"}`)
	})

	routes.RegisterStudentRoutes(mux, db)

	log.Println("Server is running at http://localhost:8080")
	log.Println("Students API: http://localhost:8080/api/students")

	if err := http.ListenAndServe(":8080", mux); err != nil {
		log.Fatal("Server error: ", err)
	}
}
