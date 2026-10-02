<?php

namespace App\Domain\Identity\Enums;

enum Permission: string
{
    case CasesCreate = 'cases.create';
    case CasesViewAll = 'cases.view_all';
    case CasesReview = 'cases.review';
    case CasesAssign = 'cases.assign';
    case CasesManage = 'cases.manage';
    case ExpertsView = 'experts.view';
    case ExpertsVerify = 'experts.verify';
    case BusinessesView = 'businesses.view';
    case KnowledgeView = 'knowledge.view';
    case KnowledgeManage = 'knowledge.manage';
    case KnowledgeApprove = 'knowledge.approve';
    case CategoriesManage = 'categories.manage';
    case AnalyticsView = 'analytics.view';
    case KpisManage = 'kpis.manage';
    case AuditView = 'audit.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case PilotManage = 'pilot.manage';
    case ReportsView = 'reports.view';
    case PartnersManage = 'partners.manage';
    case LegalReview = 'legal.review';
    case ComplaintsManage = 'complaints.manage';
    case DataRequestsManage = 'data_requests.manage';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
