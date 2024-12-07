// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract Attendance {
    struct AttendanceRecord {
        string studentId;
        string courseCode;
        string unitCode;
        string date;
    }

    AttendanceRecord[] public records;
    event AttendanceMarked(string studentId, string courseCode, string unitCode, string date);

    function markAttendance(
        string memory _studentId,
        string memory _courseCode,
        string memory _unitCode,
        string memory _date
    ) public {
        records.push(AttendanceRecord(_studentId, _courseCode, _unitCode, _date));
        emit AttendanceMarked(_studentId, _courseCode, _unitCode, _date);
    }

    function getAttendanceCount() public view returns (uint) {
        return records.length;
    }
}
