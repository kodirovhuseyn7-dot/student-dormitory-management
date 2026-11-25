# Lab 13 — Результаты проверки

## 1. Repository tests

| № | Проверка | Ожидаемый результат | Результат |
|---|---|---|---|
| 1 | StudentRepository | Получение списка студентов и общего количества | PASS |
| 2 | RoomRepository | Получение списка комнат и общего количества | PASS |
| 3 | ApplicationRepository | Получение заявок и общего количества | PASS |

## 2. CRUD tests

| № | Операция | Результат |
|---|---|---|
| 4 | Create — создание тестового студента | PASS |
| 5 | Read — чтение созданного студента | PASS |
| 6 | Update — изменение данных студента | PASS |
| 7 | Delete — удаление тестового студента | PASS |

CRUD test completed successfully. Temporary test student was created, read, updated and deleted.

## 3. Search, filters and pagination

| № | Проверка | Результат |
|---|---|---|
| 8 | Student search | PASS — 4 результата |
| 9 | Student faculty filter | PASS — проверен отдельно, 2 результата |
| 10 | Student pagination (LIMIT 2) | PASS — 2 записи |
| 11 | Room search | PASS — 4 результата |
| 12 | Room status filter | PASS — 4 результата |
| 13 | Room pagination (LIMIT 2) | PASS — 2 записи |
| 14 | Application status filter | PASS — 5 результатов |
| 15 | Application pagination (LIMIT 2) | PASS — 2 записи |

## 4. Database

- PDO connection to MySQL tested.
- Repository queries tested.
- JOIN queries tested through repository operations.
- LIMIT and OFFSET pagination tested.
- CRUD operations tested using a temporary student record.

## 5. Notes

The first faculty filter test used an incorrect faculty value and returned zero records. The filter was subsequently checked with `Faculty of Information Technology` and returned two students.

Foreign-key error handling and all server-side validation scenarios were not separately verified in this test run.

## Conclusion

Repository operations, CRUD, search, filtering and pagination were successfully tested for the demonstrated cases.
