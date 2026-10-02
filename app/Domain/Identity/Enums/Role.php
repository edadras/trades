<?php

namespace App\Domain\Identity\Enums;

enum Role: string
{
    case Business = 'business';
    case Supporter = 'supporter';
    case CaseExpert = 'case_expert';
    case ContentManager = 'content_manager';
    case OperationsManager = 'operations_manager';
    case ProductManager = 'product_manager';
    case LegalCompliance = 'legal_compliance';
    case ProgramLead = 'program_lead';
    case NetworkManager = 'network_manager';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    /** Staff roles work inside the platform team rather than as a client. */
    public function isStaff(): bool
    {
        return ! in_array($this, [self::Business, self::Supporter], true);
    }

    /** @return array<int, string> */
    public static function staffValues(): array
    {
        return array_values(array_map(fn (self $r) => $r->value, array_filter(self::cases(), fn (self $r) => $r->isStaff())));
    }

    /**
     * Permissions granted to each role.
     *
     * @return array<string, array<int, string>>
     */
    public static function permissionMap(): array
    {
        $all = Permission::values();

        return [
            self::Business->value => [Permission::CasesCreate->value],
            self::Supporter->value => [],
            self::CaseExpert->value => [
                Permission::CasesViewAll->value, Permission::CasesReview->value, Permission::CasesAssign->value,
                Permission::CasesManage->value, Permission::ExpertsView->value, Permission::KnowledgeView->value,
            ],
            self::ContentManager->value => [Permission::KnowledgeView->value, Permission::KnowledgeManage->value, Permission::KnowledgeApprove->value],
            self::OperationsManager->value => [
                Permission::CasesViewAll->value, Permission::CasesReview->value, Permission::CasesAssign->value, Permission::CasesManage->value,
                Permission::ExpertsView->value, Permission::ExpertsVerify->value, Permission::BusinessesView->value,
                Permission::AnalyticsView->value, Permission::KpisManage->value, Permission::KnowledgeView->value,
                Permission::ReportsView->value, Permission::PartnersManage->value, Permission::ComplaintsManage->value,
            ],
            self::NetworkManager->value => [
                Permission::ExpertsView->value, Permission::ExpertsVerify->value, Permission::CasesAssign->value, Permission::CasesViewAll->value,
                Permission::ReportsView->value, Permission::PartnersManage->value,
            ],
            self::ProgramLead->value => [
                Permission::AnalyticsView->value, Permission::KpisManage->value, Permission::PilotManage->value, Permission::ReportsView->value,
                Permission::PartnersManage->value, Permission::BusinessesView->value, Permission::ExpertsView->value, Permission::CasesViewAll->value,
                Permission::KnowledgeView->value,
            ],
            self::ProductManager->value => [Permission::ReportsView->value, Permission::AnalyticsView->value, Permission::KpisManage->value, Permission::KnowledgeView->value, Permission::CategoriesManage->value],
            self::LegalCompliance->value => [Permission::LegalReview->value, Permission::DataRequestsManage->value, Permission::ComplaintsManage->value, Permission::CasesViewAll->value, Permission::AuditView->value, Permission::AnalyticsView->value, Permission::BusinessesView->value, Permission::ExpertsView->value, Permission::KnowledgeApprove->value, Permission::KnowledgeView->value],
            self::Admin->value => array_values(array_diff($all, [Permission::RolesManage->value])),
            self::SuperAdmin->value => $all,
        ];
    }
}
