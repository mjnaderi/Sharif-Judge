# Users

In Sharif Judge, users are grouped into 4 groups: Admins, Head Instructors, Instructors, and Students.

## User roles
There are four user roles in Sharif Judge.

| User Role       | User Level |
| --------------- | ---------- |
| Admin           | 3          |
| Head Instructor | 2          |
| Instructor      | 1          |
| Student         | 0          |

## Permissions Table 

| Action | Admin    | Head Instructor   | Instructor   | Student |
| ------ | -------- | ----------------- | ------------ | ------- |
| Change Settings   |  ✔  |  ✘  |  ✘  |  ✘  |
| Add/Remove Users   |  ✔  |  ✘  |  ✘  |  ✘  |
| Change User Roles   |  ✔  |  ✘  |  ✘  |  ✘  |
| Add/Remove/Edit Assignment    |  ✔  |  ✔  |  ✘  |  ✘  |
| Download Tests    |  ✔  |  ✔  |  ✘  |  ✘  |
| Add/Remove/Edit Notification    |  ✔  |  ✔  |  ✘  |  ✘  |
| Rejudge   |  ✔  |  ✔  |  ✘  |  ✘  |
| View/Pause/Resume/Empty Submission Queue   |  ✔  |  ✔  |  ✘  |  ✘  |
| Detect Similar Codes   |  ✔  |  ✔  |  ✘  |  ✘  |
| View All Codes    |  ✔  |  ✔  |  ✔  |  ✘  |
| Download Final Codes    |  ✔  |  ✔  |  ✔  |  ✘  |
| Select Assignment |  ✔  |  ✔  |  ✔  |  ✔  |
| Submit            |  ✔  |  ✔  |  ✔  |  ✔  |

## Add Users

You can add multiple users by clicking on `Add Users` in `Users` page. You must provide all information in a textarea.

Lines starting with a `#` are comments. Each other line represents a user with following syntax:

    USERNAME EMAIL PASSWORD ROLE

    * Usernames may contain lowercase letters or numbers and must be between 3 and 20 characters in length.
    * Passwords must be between 6 and 30 characters in length.
    * You can use RANDOM[n] for password to generate random n-digit password.
    * ROLE must be one of these: `admin`, `head_instructor`, `instructor`, `student`

An example:

    # This is a comment!
    # This is another comment!
    instructor instructor@sharifjudge.ir 123456 head_instructor
    instructor2 instructor2@sharifjudge.ir random[7] instructor
    student1 st1@sharifjudge.ir random[6] student
    student2 st2@sharifjudge.ir random[6] student
    student3 st3@sharifjudge.ir random[6] student
    student4 st4@sharifjudge.ir random[6] student
    student5 st5@sharifjudge.ir random[6] student
    student6 st6@sharifjudge.ir random[6] student
    student7 st7@sharifjudge.ir random[6] student

