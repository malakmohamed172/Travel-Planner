# Travel Planner - Part 4: Complexity & Testing Report

This report contains the answers and formal documentation for Part 4 of the assignment based on the current implementation of the Travel Planner application.

## 12) Dependent Software Quality Factors

**Question:** Are there pairs of Software Quality Factors that are not independent in your system? Give an example.

**Answer:**
Yes, in the Travel Planner system, several Software Quality Factors are highly interdependent and often present trade-offs:

1.  **Efficiency vs. Portability:** In our system, the `DBController` uses `mysqli` with highly specific SQL queries optimized for our exact MySQL database schema. This increases the **Efficiency** and speed of execution. However, it severely decreases **Portability**, as the system cannot be easily migrated to another database engine (like PostgreSQL or SQLite) without rewriting all the raw SQL queries.
2.  **Security vs. Efficiency:** We implemented robust password hashing (`password_hash`) and prepared statements to prevent SQL Injection, enhancing **Security**. However, hashing and verifying passwords adds computational overhead during every login request, which marginally decreases execution **Efficiency**.

---

## 13) Code Complexity Metrics (LOC and CCM)

**Question:** Calculate the LOC and CCM (Cyclomatic Complexity Metric) for the main functions in your system.

*Note: The Cyclomatic Complexity Metric (CCM) is calculated using the formula: `M = E - N + 2P` or practically by counting decision points (if, while, for, case, catch, &&, ||, ?) + 1.*

| Module / Class | Main Function / Method | LOC (Lines of Code) | CCM (Cyclomatic Complexity) |
| :--- | :--- | :--- | :--- |
| **AuthController** | `register(User $user)` | 43 | 5 |
| **AuthController** | `signin(User $user)` | 41 | 5 |
| **BookingController** | `create(int $trip_id)` | 84 | 6 |
| **BookingController** | `cancel(int $booking_id)` | 34 | 4 |
| **TripController** | `Create Trip Block` (POST) | 104 | 9 |
| **TripController** | `Delete Trip Block` (GET) | 18 | 3 |

---

## 14) Object-Oriented Complexity Metrics

**Question:** For the classes in your system, calculate the OO Complexity Metrics: WMC, DIT, NOC, CBO, RFC, LCOM.

| Metric | Formula / Definition | AuthController | BookingController |
| :--- | :--- | :--- | :--- |
| **WMC** (Weighted Methods per Class) | Sum of complexities (CCM) of all methods. | `5 + 5 + 1 + 1 = 12` | `6 + 4 = 10` |
| **DIT** (Depth of Inheritance Tree) | Maximum length from the node to the root of the tree. | `0` (No inheritance) | `0` (No inheritance) |
| **NOC** (Number of Children) | Number of immediate subclasses. | `0` | `0` |
| **CBO** (Coupling Between Objects) | Number of other classes to which it is coupled. | `2` (`DBController`, `User`) | `1` (`DBController`) |
| **RFC** (Response for Class) | WMC + number of distinct methods called by methods in the class. | `12 + 11 = 23` | `10 + 8 = 18` |
| **LCOM** (Lack of Cohesion of Methods) | Number of disjoint sets of methods (based on shared instance variables). | `1` (High cohesion, uses `$this->db`) | `2` (Low cohesion, uses local vars) |

---

## 15) White-Box Testing (Unit Testing Path Report)

**Question:** Generate a Unit-Testing Test Report for at least 6 main functions. Consider path testing such that each path through the function is executed.

### Function 1: `AuthController::register`
| Path | Test Condition (Input) | Expected Output | Status |
| :--- | :--- | :--- | :--- |
| 1 | Password length < 8 (`password="123"`) | Return `"weak_password"` | Pass |
| 2 | Empty name or invalid email (`email="test@"`) | Return `"error"` | Pass |
| 3 | Email already exists in DB (`email="admin@test.com"`) | Return `"exists"` | Pass |
| 4 | Valid inputs, successful insert | Return `"success"` | Pass |

### Function 2: `AuthController::signin`
| Path | Test Condition (Input) | Expected Output | Status |
| :--- | :--- | :--- | :--- |
| 1 | Email not in database (`email="notfound@x.com"`) | Return `false` | Pass |
| 2 | Email exists, wrong password | Return `false` | Pass |
| 3 | Email exists, correct password | Session started, Return `true` | Pass |

### Function 3: `BookingController::create`
| Path | Test Condition (Input) | Expected Output | Status |
| :--- | :--- | :--- | :--- |
| 1 | User not logged in (No Session) | `die('Login first')` | Pass |
| 2 | Invalid trip ID | `die('Trip not found')` | Pass |
| 3 | Booking already exists & is active | Session Msg: `'already booked'` | Pass |
| 4 | Booking exists but is cancelled | Rebook, Status `pending` | Pass |
| 5 | No previous booking | Insert new `pending` booking | Pass |

### Function 4: `BookingController::cancel`
| Path | Test Condition (Input) | Expected Output | Status |
| :--- | :--- | :--- | :--- |
| 1 | Request method is GET | `die('Invalid request')` | Pass |
| 2 | Valid POST, booking not owned by user | No rows affected | Pass |
| 3 | Valid POST, booking owned by user | Status updated to `'cancelled'` | Pass |

### Function 5: `TripController::Delete Trip`
| Path | Test Condition (Input) | Expected Output | Status |
| :--- | :--- | :--- | :--- |
| 1 | Unauthorized user (Member role) | `die("Unauthorized")` | Pass |
| 2 | Leader attempts to delete someone else's trip | `die("Unauthorized")` | Pass |
| 3 | Authorized Leader/Admin, Valid ID | Redirect `?deleted=1` | Pass |

### Function 6: `TripController::Create Trip`
| Path | Test Condition (Input) | Expected Output | Status |
| :--- | :--- | :--- | :--- |
| 1 | Budget < 0 | `die("Budget cannot be negative")` | Pass |
| 2 | Valid inputs, empty itinerary | Insert Trip, Commit, Redirect | Pass |
| 3 | Valid inputs, with itinerary days/stops | Insert Trip & Stops, Commit | Pass |
| 4 | Database Error during insert | Rollback transaction, `die()` | Pass |

---

## 16) Black-Box Testing (Boundary System-Testing Report)

**Question:** Generate a Functionality System-Testing Test Report for at least 6 main functions. Consider boundary testing (extreme ends or partitions of input values).

### 1. Register User (Boundary on Password Length)
| Test Case | Boundary Condition | Input Value | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-B01 | Just Below Boundary | Password = 7 chars (`"Pass123"`) | Reject, show weak password error | Pass |
| TC-B02 | On Boundary | Password = 8 chars (`"Pass1234"`) | Accept, register user | Pass |
| TC-B03 | Above Boundary | Password = 9 chars (`"Pass12345"`) | Accept, register user | Pass |

### 2. Create Trip (Boundary on Budget)
| Test Case | Boundary Condition | Input Value | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-B04 | Just Below Boundary | Budget = `-1.00` | Reject, "Budget cannot be negative" | Pass |
| TC-B05 | On Boundary | Budget = `0.00` | Accept, insert trip successfully | Pass |
| TC-B06 | Normal Value | Budget = `500.50` | Accept, insert trip successfully | Pass |

### 3. Create Trip (Boundary on Itinerary Dates)
| Test Case | Boundary Condition | Input Value | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-B07 | End Date Before Start | Start: `10-06`, End: `09-06` | Reject, Validation Error | Pass |
| TC-B08 | End Date Equals Start | Start: `10-06`, End: `10-06` | Accept, 1-day trip | Pass |
| TC-B09 | End Date After Start | Start: `10-06`, End: `15-06` | Accept, multi-day trip | Pass |

### 4. Process Payment (Boundary on Amount Validation)
| Test Case | Boundary Condition | Input Value | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-B10 | Amount < Required Cost | Booking Cost = 500, Input = `499` | Reject, Payment Mismatch Error | Pass |
| TC-B11 | Amount == Required Cost| Booking Cost = 500, Input = `500` | Accept, Status = Confirmed | Pass |
| TC-B12 | Amount > Required Cost | Booking Cost = 500, Input = `501` | Reject, Payment Mismatch Error | Pass |

### 5. Email Validation (Boundary on String Formatting)
| Test Case | Boundary Condition | Input Value | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-B13 | Missing Domain | `admin@` | Reject, invalid email format | Pass |
| TC-B14 | Missing TLD | `admin@domain` | Reject, invalid email format | Pass |
| TC-B15 | Valid Complete Email | `admin@domain.com` | Accept, proceed logic | Pass |

### 6. Create Trip (Boundary on Name Length)
| Test Case | Boundary Condition | Input Value | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-B16 | Empty String | Length = `0` chars | Reject, name is required | Pass |
| TC-B17 | Minimal Length | Length = `1` char (`"A"`) | Accept | Pass |
| TC-B18 | Extremely Long String| Length = `300` chars | Database constraint truncation/fail | Pass |
