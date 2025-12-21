# krpano-cms PHP API Reference

Welcome to the **krpano-cms API**. This documentation provides a comprehensive guide to interacting with the backend of the krpano-cms, built on a REST-like architecture using PHP.

## Overview

The API predicts a predictable resource-oriented URL structure, accepts **JSON-encoded** request bodies, and returns **JSON-encoded** responses. Standard HTTP response codes are used to indicate the success or failure of an API request.

**Base URL**: `https://[your-domain]/api`

### Content-Type

All requests should (where applicable) use the `Content-Type: application/json` header, and all responses will return `Content-Type: application/json`.

---

## Authentication

The API uses two methods of authentication depending on the context: **Session-based** for admin operations and **Token-based** for public shared links.

### Admin Authentication (Session)

The admin panel uses standard PHP sessions (`PHPSESSID` cookie). You must be logged in to perform administrative actions like creating groups, uploading projects, or deleting resources.

### Public Access (Tokens)

For public "Shared Groups", authentication is handled via a `token` query parameter or JSON property.

**Example Request (cURL)**

```bash
curl "https://api.example.com/api/shared_group.php?token=sk_test_12345&group=my-group"
```

---

## Error Handling

The API uses standard HTTP status codes to indicate the success or failure of a request.

| Code    | Status                | Description                                                                 |
| :------ | :-------------------- | :-------------------------------------------------------------------------- |
| **200** | OK                    | The request was successful.                                                 |
| **400** | Bad Request           | The request was unacceptable (missing parameters, invalid format).          |
| **401** | Unauthorized          | Authentication failed or user is not logged in.                             |
| **403** | Forbidden             | The authenticated user does not have permission to access the resource.     |
| **404** | Not Found             | The requested resource (group or project) does not exist.                   |
| **405** | Method Not Allowed    | The HTTP method (e.g., GET vs. POST) is not valid for this endpoint.        |
| **409** | Conflict              | The resource already exists (e.g., creating a group with a duplicate name). |
| **500** | Internal Server Error | Something went wrong on the server side.                                    |

### Error Object

Attributes:

- `success`: `boolean` (always `false` on error)
- `message`: `string` - A human-readable message providing more details about the error.

**Example Error Response**

```json
{
	"success": false,
	"message": "En grupp med det namnet finns redan."
}
```

---

## Core Resources

### Session

Manage the admin session state.

#### Login

`POST /login.php`

Authenticate as an administrator.

**Parameters**

| Parameter  | Type   | Required | Description         |
| :--------- | :----- | :------- | :------------------ |
| `username` | string | **Yes**  | The admin username. |
| `password` | string | **Yes**  | The admin password. |

**Response**

```json
{
	"success": true
}
```

---

### Dashboard

Retrieve the global state of the CMS.

#### Retrieve Dashboard

`GET /dashboard.php`

Returns a list of all groups, root projects, and system statistics.

**Response**

```json
{
	"success": true,
	"groups": {
		"Group A": ["Project 1", "Project 2"],
		"Group B": []
	},
	"rootProjects": ["Legacy Project 1"],
	"authData": {
		"Group A": { "token": "..." }
	},
	"stats": {
		"maxUploadMb": 128,
		"phpVersion": "8.1.0"
	}
}
```

---

### Groups

Groups are folders that contain multiple projects.

#### Create a Group

`POST /create_group.php`

Creates a new empty group (directory).

**Parameters**

| Parameter    | Type   | Required | Description                                                      |
| :----------- | :----- | :------- | :--------------------------------------------------------------- |
| `group_name` | string | **Yes**  | The name of the new group. Special characters will be sanitized. |

**Response**

```json
{
	"success": true,
	"message": "Gruppen skapades!"
}
```

#### Retrieve a Shared Group

`GET /shared_group.php`

Retrieve details of a group using a public access token.

**Parameters**

| Parameter | Type   | Required | Description                               |
| :-------- | :----- | :------- | :---------------------------------------- |
| `token`   | string | **Yes**  | The access token for the group.           |
| `group`   | string | Optional | The name of the group (if not inferable). |

**Response**

```json
{
	"success": true,
	"group_name": "My Shared Group",
	"projects": ["Tour 1", "Tour 2"],
	"is_protected": true
}
```

_Note: If `token` is invalid or missing for a protected group, returns 403 Forbidden._

#### Rename a Group

`POST /rename.php`

Renames an existing group.

**Parameters**

| Parameter  | Type   | Required | Description                                               |
| :--------- | :----- | :------- | :-------------------------------------------------------- |
| `old_name` | string | **Yes**  | Current name of the group.                                |
| `new_name` | string | **Yes**  | New name for the group.                                   |
| `group`    | string | No       | Leave empty or omit when renaming a group (vs a project). |

**Response**

```json
{
	"success": true
}
```

#### Delete a Group

`POST /delete.php`

Deletes a group and **all its contents**.

**Parameters**

| Parameter | Type   | Required | Description                                     |
| :-------- | :----- | :------- | :---------------------------------------------- |
| `group`   | string | **Yes**  | The name of the group to delete.                |
| `project` | string | No       | **Must be omitted** to delete the entire group. |

**Response**

```json
{
	"success": true
}
```

---

### Projects

Projects are individual krpano tours contained within groups (or at the root).

#### Upload a Project

`POST /upload.php`

Upload a ZIP file containing a project. The ZIP will be extracted into a folder named after the ZIP file (sanitized).

**Parameters**

| Parameter | Type   | Required | Description                           |
| :-------- | :----- | :------- | :------------------------------------ |
| `file`    | file   | **Yes**  | A ZIP file containing the project.    |
| `group`   | string | **Yes**  | The name of the group to upload into. |

**Response**

```json
{
	"success": true,
	"message": "Projekt uppladdat och skapat!"
}
```

#### Move a Project

`POST /move.php`

Move a project from one group to another.

**Parameters**

| Parameter        | Type   | Required    | Description                                               |
| :--------------- | :----- | :---------- | :-------------------------------------------------------- |
| `project`        | string | **Yes**     | The name of the project folder to move.                   |
| `current_group`  | string | **Yes**     | The group the project is currently in.                    |
| `target_group`   | string | **Yes**     | The destination group. Use `"NEW"` to create a new group. |
| `new_group_name` | string | Conditional | Required if `target_group` is `"NEW"`.                    |

**Response**

```json
{
	"success": true
}
```

#### Rename a Project

`POST /rename.php`

Renames a project within a group.

**Parameters**

| Parameter  | Type   | Required | Description                       |
| :--------- | :----- | :------- | :-------------------------------- |
| `group`    | string | **Yes**  | The group containing the project. |
| `old_name` | string | **Yes**  | Current name of the project.      |
| `new_name` | string | **Yes**  | New name for the project.         |

**Response**

```json
{
	"success": true
}
```

#### Delete a Project

`POST /delete.php`

Deletes a single project.

**Parameters**

| Parameter | Type   | Required | Description                        |
| :-------- | :----- | :------- | :--------------------------------- |
| `group`   | string | **Yes**  | The group containing the project.  |
| `project` | string | **Yes**  | The name of the project to delete. |

**Response**

```json
{
	"success": true
}
```
