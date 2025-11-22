import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Switch,
  Alert,
  RefreshControl,
} from 'react-native';
import { getPreferences, updatePreferences } from '../services/notificationService';
import Loading from '../components/Loading';
import colors from '../config/colors';

export default function NotificationSettingsScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [preferences, setPreferences] = useState({
    payment_reminders: true,
    contract_updates: true,
    promotions: true,
    system_notifications: true,
    maintenance_alerts: true,
    chat_messages: true,
  });

  useEffect(() => {
    loadPreferences();
  }, []);

  const loadPreferences = async () => {
    try {
      setLoading(true);
      const response = await getPreferences();
      if (response.success && response.data?.preferences) {
        setPreferences(response.data.preferences);
      }
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de charger les préférences');
      console.error('Error loading preferences:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleToggle = async (key, value) => {
    const newPreferences = { ...preferences, [key]: value };
    setPreferences(newPreferences);

    try {
      setSaving(true);
      await updatePreferences({ [key]: value });
    } catch (error) {
      // Revert on error
      setPreferences(preferences);
      Alert.alert('Erreur', 'Impossible de mettre à jour les préférences');
      console.error('Error updating preferences:', error);
    } finally {
      setSaving(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadPreferences();
  };

  if (loading) {
    return <Loading />;
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
      }
    >
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Notifications importantes</Text>
        <Text style={styles.sectionDescription}>
          Ces notifications concernent vos paiements et contrats
        </Text>

        <View style={styles.settingItem}>
          <View style={styles.settingInfo}>
            <Text style={styles.settingTitle}>Rappels de paiement</Text>
            <Text style={styles.settingDescription}>
              Recevez des rappels avant les échéances de paiement
            </Text>
          </View>
          <Switch
            value={preferences.payment_reminders}
            onValueChange={(value) => handleToggle('payment_reminders', value)}
            disabled={saving}
            trackColor={{ false: colors.gray, true: colors.primary }}
            thumbColor={colors.white}
          />
        </View>

        <View style={styles.settingItem}>
          <View style={styles.settingInfo}>
            <Text style={styles.settingTitle}>Mises à jour des contrats</Text>
            <Text style={styles.settingDescription}>
              Notifications sur l'expiration et les modifications de contrats
            </Text>
          </View>
          <Switch
            value={preferences.contract_updates}
            onValueChange={(value) => handleToggle('contract_updates', value)}
            disabled={saving}
            trackColor={{ false: colors.gray, true: colors.primary }}
            thumbColor={colors.white}
          />
        </View>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Alertes système</Text>
        <Text style={styles.sectionDescription}>
          Notifications concernant votre box et la maintenance
        </Text>

        <View style={styles.settingItem}>
          <View style={styles.settingInfo}>
            <Text style={styles.settingTitle}>Alertes de maintenance</Text>
            <Text style={styles.settingDescription}>
              Notifications pour les opérations de maintenance
            </Text>
          </View>
          <Switch
            value={preferences.maintenance_alerts}
            onValueChange={(value) => handleToggle('maintenance_alerts', value)}
            disabled={saving}
            trackColor={{ false: colors.gray, true: colors.primary }}
            thumbColor={colors.white}
          />
        </View>

        <View style={styles.settingItem}>
          <View style={styles.settingInfo}>
            <Text style={styles.settingTitle}>Notifications système</Text>
            <Text style={styles.settingDescription}>
              Informations importantes sur votre compte
            </Text>
          </View>
          <Switch
            value={preferences.system_notifications}
            onValueChange={(value) => handleToggle('system_notifications', value)}
            disabled={saving}
            trackColor={{ false: colors.gray, true: colors.primary }}
            thumbColor={colors.white}
          />
        </View>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Communications</Text>
        <Text style={styles.sectionDescription}>
          Messages et offres promotionnelles
        </Text>

        <View style={styles.settingItem}>
          <View style={styles.settingInfo}>
            <Text style={styles.settingTitle}>Messages du chat</Text>
            <Text style={styles.settingDescription}>
              Réponses du support et du chatbot
            </Text>
          </View>
          <Switch
            value={preferences.chat_messages}
            onValueChange={(value) => handleToggle('chat_messages', value)}
            disabled={saving}
            trackColor={{ false: colors.gray, true: colors.primary }}
            thumbColor={colors.white}
          />
        </View>

        <View style={styles.settingItem}>
          <View style={styles.settingInfo}>
            <Text style={styles.settingTitle}>Promotions</Text>
            <Text style={styles.settingDescription}>
              Offres spéciales et réductions
            </Text>
          </View>
          <Switch
            value={preferences.promotions}
            onValueChange={(value) => handleToggle('promotions', value)}
            disabled={saving}
            trackColor={{ false: colors.gray, true: colors.primary }}
            thumbColor={colors.white}
          />
        </View>
      </View>

      <View style={styles.footer}>
        <Text style={styles.footerText}>
          Vos préférences sont enregistrées automatiquement
        </Text>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  section: {
    backgroundColor: colors.white,
    marginTop: 20,
    paddingHorizontal: 20,
    paddingVertical: 15,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: colors.text,
    marginBottom: 5,
  },
  sectionDescription: {
    fontSize: 14,
    color: colors.textSecondary,
    marginBottom: 15,
  },
  settingItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 15,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  settingInfo: {
    flex: 1,
    marginRight: 15,
  },
  settingTitle: {
    fontSize: 16,
    fontWeight: '500',
    color: colors.text,
    marginBottom: 4,
  },
  settingDescription: {
    fontSize: 13,
    color: colors.textSecondary,
  },
  footer: {
    padding: 20,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 13,
    color: colors.textSecondary,
    textAlign: 'center',
  },
});
