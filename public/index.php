<?php
include ("config/db_connect.php");
echo "Research Management System is Working !! <br>";
?>


<form action="add_researcher.php" method="POST">
    <input type="text" name="name" placeholder="Researcher Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <select name="department_id">
        <option value="1">Computer Science</option>
        <option value="2">Mechanical Engineering</option>
        <option value="3">Biology</option>
        <option value="4">Physics</option>
        <option value="5">Chemistry</option>
        <option value="6">Mathematics</option>
        <!-- Populate with department data from the database -->
    </select>
    <input type="text" name="research_interest" placeholder="Research Interest">
    <button type="submit">Add Researcher</button>
</form>

