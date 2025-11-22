#!/bin/bash

# Script pour compléter les migrations restantes
cd /home/user/boxnew/boxibox-app

echo "Completion des migrations restantes..."

# Liste des fichiers à vérifier
MIGRATIONS=(
    "create_notifications_table"
    "create_pricing_rules_table"
    "create_subscriptions_table"
    "create_floor_plans_table"
)

# Vérifier lesquelles sont déjà complétées
for migration in "${MIGRATIONS[@]}"; do
    file=$(find database/migrations -name "*_${migration}.php")
    if [ -f "$file" ]; then
        lines=$(grep -c "Schema::create" "$file")
        if [ $lines -eq 1 ]; then
            echo "❌ $migration - NON complétée"
        else
            echo "✅ $migration - Complétée"
        fi
    fi
done

echo ""
echo "Migrations complétées manuellement:"
echo "✅ tenants, sites, buildings, floors, boxes"
echo "✅ customers, contracts, invoices, payments, messages"
echo ""
echo "Restantes à compléter: notifications, pricing_rules, subscriptions, floor_plans"
echo ""
echo "Référence: Voir COMPLETE_REMAINING_MIGRATIONS.md pour les templates"
