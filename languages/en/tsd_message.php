<?php

return [
    // Authentication & Authorization
    'unauthenticated' => 'You must login to continue.',
    'forbidden' => 'You do not have permission.',

    // Not Found
    'notFound' => 'Data not found.',
    'emptyLoadedName' => ':name not found.',

    // Server Errors
    'serverError' => 'A server error occurred.',
    'databaseError' => 'A database error occurred.',

    // Method & Request Errors
    'methodNotAllowed' => 'Method not allowed.',
    'fileTooLarge' => 'The file is too large.',
    'sessionExpired' => 'Your session has expired. Please login again.',
    'tooManyRequests' => 'Too many requests. Please try again later.',

    // Transaction Messages
    'mustUseTransaction' => 'This method must be called within DB::transaction to maintain data consistency.',
    'transactionRequired' => 'This operation must be executed within a database transaction. Please contact administrator.',
    'transactionRequiredForOperation' => 'The :operation operation requires a database transaction. Ensure the method is called within DB::transaction.',

    // Success Messages
    'successLoaded' => 'Successfully loaded data.',
    'successSaved' => 'Successfully saved data.',
    'successUpdated' => 'Successfully updated data.',
    'successDeleted' => 'Successfully deleted data.',
    'failedSaved' => 'An error occurred while saving data.',
    'failedUpdated' => 'An error occurred while updating data.',
    'failedDeleted' => 'An error occurred while deleting data.',
    'emptyLoaded' => 'No data available to load.',

    // Validation Messages
    'moreError' => ' (and :count other error).',
    'duplicate' => 'The :attribute has already been taken.',

    // Auth & Validation Messages
    'invalidLogin' => 'Username or Password invalid.',
    'successLogin' => 'User logged in successfully.',
];