import React from "react";
import UserInfoForm from "./UserInfoForm";
import ChangePasswordForm from "./ChangePasswordForm";

const CombinedProfileForm = () => {
    return (
        <div className="max-w-3xl mx-auto sm:p-2">
            <UserInfoForm />
            <ChangePasswordForm />
        </div>
    );
};

export default CombinedProfileForm;
